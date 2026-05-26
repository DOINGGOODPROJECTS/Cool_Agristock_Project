<?php

namespace App\Http\Controllers\Webhook;

use AfricasTalking\SDK\AfricasTalking;
use App\Models\MemberPhone;
use App\Models\SyncSession;
use App\Services\Sync\ReconciliationEngine;
use App\Services\Sync\SmsParser;
use App\Services\Sync\SyncPermissionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Receives incoming SMS from Africa's Talking and converts them into
 * InventoryOps via ReconciliationEngine::processBatch().
 *
 * Route (outside auth middleware):
 *   POST /webhook/sms
 *
 * Africa's Talking delivers:
 *   from    — sender MSISDN e.g. +2250700000000
 *   to      — your shortcode / sender ID
 *   text    — raw SMS body
 *   date    — ISO-8601 timestamp
 *   id      — AT message ID
 *
 * Signature verification:
 *   Header: X-AT-Checksum = base64_encode(hash_hmac('sha256', body, AT_API_KEY, true))
 *   Verification is skipped when AT_WEBHOOK_KEY is not set (local/dev).
 */
class SmsController extends Controller
{
    public function __construct(
        private SmsParser            $parser,
        private ReconciliationEngine $engine,
        private SyncPermissionService $perms,
    ) {}

    public function __invoke(Request $request): \Illuminate\Http\Response
    {
        // ── 1. Verify Africa's Talking signature ────────────────────────
        if (! $this->verifySignature($request)) {
            Log::warning('SMS webhook: invalid signature', ['ip' => $request->ip()]);
            return response('Unauthorized', 401);
        }

        $from = $this->normalisePhone((string) $request->input('from', ''));
        $text = trim((string) $request->input('text', ''));
        $to   = (string) $request->input('to', '');

        Log::info('SMS webhook received', ['from' => $from, 'to' => $to, 'text' => $text]);

        // ── 2. Look up user via member_phones ───────────────────────────
        $memberPhone = MemberPhone::where('phone', $from)
            ->with('user')
            ->first();

        if (! $memberPhone || ! $memberPhone->user) {
            $this->reply($from, "Numéro non enregistré. Contactez votre administrateur AgriStock.");
            return response('OK', 200);
        }

        $user = $memberPhone->user;

        // ── 3. Permission check — sync.push ────────────────────────────
        if (! $this->perms->can($user, 'sync.push')) {
            Log::warning('SMS webhook: push denied', ['user_id' => $user->id, 'group' => $user->group_id]);
            $this->reply(
                $from,
                "Accès refusé. Votre compte ({$user->name}) n'est pas autorisé à soumettre des opérations de stock."
            );
            return response('OK', 200);
        }

        // ── 4. Parse the SMS ────────────────────────────────────────────
        $parsed = $this->parser->parse($text);

        if ($parsed === null) {
            $this->reply(
                $from,
                "Format non reconnu. " . $this->parser->helpText()
            );
            return response('OK', 200);
        }

        // ── 5. Build full op payload ────────────────────────────────────
        $opId    = (string) Str::uuid();
        $opData  = array_merge($parsed, [
            'op_id'             => $opId,
            'user_id'           => $user->id,
            'device_id'         => 'sms:' . $from,
            'logical_seq'       => time(),
            'stock_id'          => null,
            'client_created_at' => now()->toIso8601String(),
        ]);

        // ── 6. Create a one-shot sync session for this SMS ──────────────
        $session = SyncSession::create([
            'session_id'         => (string) Str::uuid(),
            'user_id'            => $user->id,
            'device_id'          => 'sms:' . $from,
            'ops_submitted'      => 0,
            'ops_applied'        => 0,
            'ops_conflicted'     => 0,
            'status'             => 'in_progress',
            'client_logical_seq' => $opData['logical_seq'],
        ]);

        // ── 7. Process via ReconciliationEngine ─────────────────────────
        try {
            $counts = $this->engine->processBatch([$opData], $user, $session);
        } catch (\Throwable $e) {
            Log::error('SMS webhook: engine error', ['error' => $e->getMessage(), 'op_id' => $opId]);
            $this->reply($from, "Erreur système. Votre opération n'a pas été enregistrée. Réessayez.");
            return response('OK', 200);
        }

        // ── 8. Build reply ──────────────────────────────────────────────
        $opTypeLabels = [
            'stock_in'   => 'Entrée',
            'stock_out'  => 'Sortie',
            'adjustment' => 'Ajustement',
            'spoilage'   => 'Perte',
        ];
        $typeLabel = $opTypeLabels[$parsed['op_type']] ?? $parsed['op_type'];
        $qtyAbs    = abs($parsed['quantity_delta']);

        if ($counts[ReconciliationEngine::RESULT_APPLIED] > 0) {
            $reply = sprintf(
                "✓ AgriStock: %s de %.0f kg enregistrée. Ref: %s",
                $typeLabel,
                $qtyAbs,
                substr($opId, 0, 8)
            );
        } elseif ($counts[ReconciliationEngine::RESULT_CONFLICT] > 0) {
            $reply = sprintf(
                "⚠ AgriStock: %s de %.0f kg reçue mais en CONFLIT. Un superviseur doit la valider. Ref: %s",
                $typeLabel,
                $qtyAbs,
                substr($opId, 0, 8)
            );
        } else {
            $reply = "AgriStock: Opération déjà reçue (doublon). Ref: " . substr($opId, 0, 8);
        }

        $this->reply($from, $reply);

        return response('OK', 200);
    }

    // ── Private helpers ──────────────────────────────────────────────────

    /**
     * Verify the Africa's Talking HMAC-SHA256 webhook signature.
     * Returns true when the key is not configured (local / dev mode).
     *
     * Header: X-AT-Checksum = base64(HMAC-SHA256(body, AT_WEBHOOK_KEY))
     */
    private function verifySignature(Request $request): bool
    {
        $secret = config('services.africastalking.webhook_key');

        if (empty($secret)) {
            return true; // Verification skipped — set AT_WEBHOOK_KEY in .env for production
        }

        $header = $request->header('X-AT-Checksum') ?? $request->header('X-Africastalking-Signature', '');

        if (empty($header)) {
            return false;
        }

        $body     = $request->getContent();
        $expected = base64_encode(hash_hmac('sha256', $body, $secret, true));

        return hash_equals($expected, $header);
    }

    /**
     * Normalise phone numbers to E.164 format.
     * Africa's Talking delivers numbers like +2250700000000 or 2250700000000.
     */
    private function normalisePhone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', $phone);

        if (str_starts_with($phone, '+')) {
            return $phone;
        }
        if (str_starts_with($phone, '00')) {
            return '+' . substr($phone, 2);
        }
        return '+' . $phone;
    }

    /**
     * Send an SMS reply via the Africa's Talking SDK.
     * Logs the reply content regardless; SDK errors are caught and logged
     * so a send failure never causes the webhook to return non-200.
     */
    private function reply(string $to, string $message): void
    {
        Log::info('SMS webhook reply', ['to' => $to, 'message' => $message]);

        $apiKey   = config('services.africastalking.api_key');
        $username = config('services.africastalking.username');
        $from     = config('services.africastalking.sender_id');

        if (empty($apiKey) || empty($username) || $apiKey === 'sandbox') {
            // Sandbox or unconfigured — log only, no real send
            Log::debug('SMS reply (sandbox / unconfigured — not sent)', ['to' => $to]);
            return;
        }

        try {
            $AT  = new AfricasTalking($username, $apiKey);
            $sms = $AT->sms();

            $params = [
                'to'      => [$to],
                'message' => $message,
            ];

            if (! empty($from)) {
                $params['from'] = $from;
            }

            $sms->send($params);
        } catch (\Throwable $e) {
            Log::error('SMS webhook: reply send failed', ['to' => $to, 'error' => $e->getMessage()]);
        }
    }
}
