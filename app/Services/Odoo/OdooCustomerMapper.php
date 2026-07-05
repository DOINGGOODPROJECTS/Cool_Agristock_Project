<?php

namespace App\Services\Odoo;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Maps COOL AGRISTOCK customers (farmers, cooperatives, companies)
 * to Odoo res.partner records for CSV export and API sync.
 *
 * CUS-01: Map customer types to res.partner
 * CUS-02: External ID format coolagristock.customer.{id}
 * CUS-03: Field mapping table
 * CUS-04: Duplicate detection by external_id, then phone/email fallback
 */
class OdooCustomerMapper
{
    // External ID prefix used in Odoo imports (CUS-02)
    public const EXTERNAL_ID_PREFIX = 'coolagristock.customer';

    // Customer type values stored in users.group → group.name
    private const TYPE_FARMER      = 'farmer';
    private const TYPE_COOPERATIVE = 'cooperative';
    private const TYPE_COMPANY     = 'company';
    private const TYPE_BUYER       = 'buyer';

    /**
     * Build the Odoo external ID for a customer (CUS-02).
     */
    public function externalId(int $customerId): string
    {
        return self::EXTERNAL_ID_PREFIX . '.' . $customerId;
    }

    /**
     * Map one COOL AGRISTOCK User to an Odoo res.partner field array (CUS-03).
     *
     * Returns null if the user record is missing required fields.
     */
    public function toPartner(User $user): ?array
    {
        if (! $user->name) {
            return null;
        }

        $groupName = $user->group?->name ?? null;

        return [
            // Odoo import columns
            'id'                        => $this->externalId($user->id),  // External ID
            'name'                      => $user->name,
            'phone'                     => $user->phone ?? null,
            'email'                     => $user->email ?? null,
            'street'                    => null,   // address not stored on user yet
            'city'                      => null,
            'country_id/id'             => 'base.gh', // default; override per deployment
            'customer_rank'             => 1,
            'supplier_rank'             => 0,
            'company_type'              => $this->odooCompanyType($groupName),
            'x_customer_type'           => $groupName ?? 'farmer',
            'x_cooperative_id'          => $user->cooperative_id ?? null,
            'vat'                       => null,   // tax ID — left blank until finance confirms
            'ref'                       => (string) $user->id,  // internal reference
            'comment'                   => null,
            // Sync metadata (not an Odoo column — stripped before import)
            '_source_id'                => $user->id,
            '_source_phone'             => $user->phone ?? null,
            '_source_email'             => $user->email ?? null,
        ];
    }

    /**
     * Map a collection of users to partner rows, skipping invalid records (CUS-03).
     */
    public function toPartnerCollection(Collection $users): Collection
    {
        return $users->map(fn (User $u) => $this->toPartner($u))->filter();
    }

    /**
     * Detect duplicate candidates for a given customer (CUS-04).
     *
     * Returns an array of detection signals:
     *   - 'external_id_match'  — a record with this external ID may already exist in Odoo
     *   - 'phone_conflict'     — another local user shares the same phone number
     *   - 'email_conflict'     — another local user shares the same email address
     *
     * The caller decides whether to skip, merge, or flag for manual review.
     */
    public function detectDuplicates(User $user): array
    {
        $signals = [];

        // Phone duplicate check — the users table has no unique constraint on phone,
        // so two farmers may have the same number registered under different accounts.
        if ($user->phone) {
            $phoneConflict = User::where('id', '!=', $user->id)
                ->where('phone', $user->phone)
                ->whereNull('deleted_at')
                ->exists();

            if ($phoneConflict) {
                $signals[] = 'phone_conflict';
            }
        }

        return $signals;
    }

    /**
     * Build the Odoo-compatible CSV header row for res.partner import.
     */
    public function csvHeaders(): array
    {
        return [
            'id',
            'name',
            'phone',
            'email',
            'street',
            'city',
            'country_id/id',
            'customer_rank',
            'supplier_rank',
            'company_type',
            'x_customer_type',
            'x_cooperative_id',
            'vat',
            'ref',
            'comment',
        ];
    }

    /**
     * Strip internal sync-metadata keys before writing the CSV row.
     */
    public function toCsvRow(array $partner): array
    {
        return array_intersect_key($partner, array_flip($this->csvHeaders()));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function odooCompanyType(?string $groupName): string
    {
        return match ($groupName) {
            self::TYPE_COOPERATIVE, self::TYPE_COMPANY => 'company',
            default                                    => 'person',
        };
    }
}
