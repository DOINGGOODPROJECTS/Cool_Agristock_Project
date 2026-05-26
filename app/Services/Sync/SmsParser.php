<?php

namespace App\Services\Sync;

use App\Models\Product;
use App\Models\Storage;
use Illuminate\Support\Str;

/**
 * Parses plain-text SMS commands into InventoryOp payloads.
 *
 * Supported formats (case-insensitive, accent-insensitive):
 *   ENTREE  <qty> kg <product> <S{n}>   → stock_in,   +qty
 *   SORTIE  <qty> kg <product> <S{n}>   → stock_out,  -qty
 *   POURRI  <qty> kg <product> <S{n}>   → spoilage,   -qty
 *   AJUSTER <qty> kg <product> <S{n}>   → adjustment, +qty
 *
 * Storage codes S1–S9 map to storages ordered by id (S1 = first, S2 = second…).
 * Product names are matched case-insensitively against products.name.
 */
class SmsParser
{
    private const COMMANDS = [
        'entree'  => ['op_type' => 'stock_in',   'sign' => +1],
        'entrée'  => ['op_type' => 'stock_in',   'sign' => +1],
        'sortie'  => ['op_type' => 'stock_out',  'sign' => -1],
        'pourri'  => ['op_type' => 'spoilage',   'sign' => -1],
        'pourrie' => ['op_type' => 'spoilage',   'sign' => -1],
        'ajuster' => ['op_type' => 'adjustment', 'sign' => +1],
        'ajuste'  => ['op_type' => 'adjustment', 'sign' => +1],
        'ajusté'  => ['op_type' => 'adjustment', 'sign' => +1],
    ];

    /**
     * Parse an SMS body and resolve product + storage IDs.
     *
     * Returns an associative array ready to pass into processBatch() as $opsData,
     * or null when the format is not recognised.
     *
     * Returned keys: op_type, quantity_delta, unit, product_id, storage_id, notes
     * Missing keys that processBatch needs (op_id, user_id, device_id, …) are
     * added by SmsController before calling the engine.
     *
     * @return array{op_type:string,quantity_delta:float,unit:string,product_id:int,storage_id:int,notes:string}|null
     */
    public function parse(string $text): ?array
    {
        $text = trim($text);

        // ── Pattern:  <COMMAND> <qty> kg <product name> <S{n}> ──────────
        // Unit "kg" is optional; product name may be multiple words.
        // Storage code S1–S9 must be at the end.
        $pattern = '/^(\S+)\s+(\d+(?:[.,]\d+)?)\s*(?:kg)?\s+(.+?)\s+(S\d+)$/iu';

        if (! preg_match($pattern, $text, $m)) {
            return null;
        }

        [, $rawCmd, $rawQty, $rawProduct, $rawStorage] = $m;

        // ── Resolve command ──────────────────────────────────────────────
        $cmdKey = mb_strtolower($this->stripAccents($rawCmd));
        $cmd    = self::COMMANDS[$cmdKey] ?? null;

        if ($cmd === null) {
            return null;
        }

        // ── Resolve quantity ─────────────────────────────────────────────
        $qty = (float) str_replace(',', '.', $rawQty);
        if ($qty <= 0) {
            return null;
        }
        $delta = $cmd['sign'] * $qty;

        // ── Resolve product name → products.id ───────────────────────────
        $productId = $this->resolveProduct(trim($rawProduct));
        if ($productId === null) {
            return null;
        }

        // ── Resolve storage code S{n} → storages.id ─────────────────────
        $storageId = $this->resolveStorage(strtoupper(trim($rawStorage)));
        if ($storageId === null) {
            return null;
        }

        return [
            'op_type'        => $cmd['op_type'],
            'quantity_delta' => $delta,
            'unit'           => 'kg',
            'product_id'     => $productId,
            'storage_id'     => $storageId,
            'notes'          => "SMS: {$text}",
        ];
    }

    /**
     * Returns a human-readable French error for an unrecognised SMS.
     */
    public function helpText(): string
    {
        return implode("\n", [
            'Format attendu:',
            'ENTREE 50 kg tomates S1',
            'SORTIE 20 kg manioc S2',
            'POURRI 5 kg bananes S1',
            'AJUSTER 100 kg ignames S3',
        ]);
    }

    // ── Private helpers ──────────────────────────────────────────────────

    /**
     * Case/accent-insensitive product lookup.
     * Tries exact match first, then a LIKE/contains fallback.
     */
    private function resolveProduct(string $name): ?int
    {
        $normalised = mb_strtolower($this->stripAccents($name));

        $hit = Product::all()->first(function ($p) use ($normalised) {
            return mb_strtolower($this->stripAccents($p->name)) === $normalised;
        });

        if (! $hit) {
            // Bidirectional partial match:
            // "tomates" (SMS) matches "Tomate" (DB) because "tomates" contains "tomate"
            // "Tomate fraîche" (DB) matches "tomate" (SMS) because DB name contains SMS term
            $hit = Product::all()->first(function ($p) use ($normalised) {
                $dbName = mb_strtolower($this->stripAccents($p->name));
                return Str::contains($dbName, $normalised)
                    || Str::contains($normalised, $dbName);
            });
        }

        return $hit?->id;
    }

    /**
     * Maps S1 → 1st storage by id, S2 → 2nd, etc.
     * Storages are ordered ascending by id so the mapping is stable.
     */
    private function resolveStorage(string $code): ?int
    {
        if (! preg_match('/^S(\d+)$/i', $code, $m)) {
            return null;
        }

        $n = (int) $m[1];
        if ($n < 1) {
            return null;
        }

        return Storage::orderBy('id')->skip($n - 1)->value('id');
    }

    /**
     * Strip accents from a UTF-8 string (for loose matching).
     */
    private function stripAccents(string $str): string
    {
        $map = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'ñ' => 'n', 'ç' => 'c',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ä' => 'A', 'Ã' => 'A',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Ö' => 'O', 'Õ' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ñ' => 'N', 'Ç' => 'C',
        ];
        return strtr($str, $map);
    }
}
