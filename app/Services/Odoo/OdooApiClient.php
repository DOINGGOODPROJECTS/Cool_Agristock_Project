<?php

namespace App\Services\Odoo;

use RuntimeException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Thin Odoo JSON-RPC client.
 *
 * Uses Laravel's HTTP client (no PHP xmlrpc extension required).
 * Endpoints: POST /jsonrpc  — service=common for auth, service=object for model calls.
 */
class OdooApiClient
{
    private ?int $uid = null;
    private int  $callId = 1;

    public function __construct(
        private readonly string $url,
        private readonly string $database,
        private readonly string $username,
        private readonly string $apiKey,
        private readonly int    $timeout,
        private readonly bool   $dryRun,
    ) {}

    public function authenticate(): int
    {
        if ($this->uid !== null) {
            return $this->uid;
        }

        if ($this->dryRun) {
            Log::info('[Odoo DryRun] authenticate() — skipped, returning uid=1');
            $this->uid = 1;
            return $this->uid;
        }

        $result = $this->jsonrpc('common', 'authenticate', [
            $this->database,
            $this->username,
            $this->apiKey,
            [],
        ]);

        if (! is_int($result) || $result === 0) {
            throw new RuntimeException(
                "Odoo authentication failed for [{$this->username}] on [{$this->database}]. "
                . "Check ODOO_URL, ODOO_DATABASE, ODOO_USERNAME and ODOO_API_KEY."
            );
        }

        $this->uid = $result;
        Log::info("[Odoo] Authenticated as uid={$this->uid}");

        return $this->uid;
    }

    public function searchRead(string $model, array $domain = [], array $fields = [], int $limit = 100): array
    {
        if ($this->dryRun) {
            Log::info("[Odoo DryRun] searchRead({$model})", compact('domain', 'fields', 'limit'));
            return [];
        }

        return $this->execute($model, 'search_read', [$domain], [
            'fields' => $fields,
            'limit'  => $limit,
        ]);
    }

    public function create(string $model, array $values): int
    {
        if ($this->dryRun) {
            Log::info("[Odoo DryRun] create({$model})", $values);
            return 0;
        }

        return $this->execute($model, 'create', [$values]);
    }

    public function write(string $model, array $ids, array $values): bool
    {
        if ($this->dryRun) {
            Log::info("[Odoo DryRun] write({$model})", compact('ids', 'values'));
            return true;
        }

        return $this->execute($model, 'write', [$ids, $values]);
    }

    public function search(string $model, array $domain, int $limit = 100): array
    {
        if ($this->dryRun) {
            Log::info("[Odoo DryRun] search({$model})", compact('domain'));
            return [];
        }

        return $this->execute($model, 'search', [$domain], ['limit' => $limit]);
    }

    public function callAction(string $model, array $ids, string $action): mixed
    {
        if ($this->dryRun) {
            Log::info("[Odoo DryRun] callAction({$model}, {$action})", $ids);
            return true;
        }

        return $this->execute($model, $action, [$ids]);
    }

    public function findByExternalId(string $model, string $externalId): ?int
    {
        if ($this->dryRun) {
            Log::info("[Odoo DryRun] findByExternalId({$model}, {$externalId})");
            return null;
        }

        $results = $this->searchRead(
            'ir.model.data',
            [['name', '=', str_replace('.', '_', $externalId)], ['model', '=', $model]],
            ['res_id'],
            1
        );

        return $results[0]['res_id'] ?? null;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function execute(string $model, string $method, array $args = [], array $kwargs = []): mixed
    {
        $uid = $this->authenticate();

        return $this->jsonrpc('object', 'execute_kw', [
            $this->database,
            $uid,
            $this->apiKey,
            $model,
            $method,
            $args,
            $kwargs,
        ]);
    }

    private function jsonrpc(string $service, string $method, array $args): mixed
    {
        $payload = [
            'jsonrpc' => '2.0',
            'method'  => 'call',
            'id'      => $this->callId++,
            'params'  => [
                'service' => $service,
                'method'  => $method,
                'args'    => $args,
            ],
        ];

        $response = Http::timeout($this->timeout)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("{$this->url}/jsonrpc", $payload);

        if ($response->failed()) {
            throw new RuntimeException(
                "Odoo JSON-RPC HTTP error [{$response->status()}] calling {$service}.{$method}."
            );
        }

        $body = $response->json();

        if (isset($body['error'])) {
            $err = $body['error'];
            $msg = $err['data']['message'] ?? $err['message'] ?? json_encode($err);
            throw new RuntimeException("Odoo error [{$err['code']}]: {$msg}");
        }

        return $body['result'] ?? null;
    }
}
