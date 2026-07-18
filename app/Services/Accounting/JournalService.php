<?php

namespace App\Services\Accounting;

use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class JournalService
{
    public function createEntry(array $data, array $lines, int $createdBy, bool $skipBalanceCheck = false): JournalEntry
    {
        return DB::transaction(function () use ($data, $lines, $createdBy, $skipBalanceCheck) {
            $entry = JournalEntry::create(array_merge($data, [
                'reference'   => JournalEntry::nextReference(),
                'created_by'  => $createdBy,
                'status'      => 'draft',
                'odoo_status' => 'not_queued',
            ]));

            foreach ($lines as $line) {
                $entry->lines()->create($line);
            }

            $entry->recalculateTotals();

            return $entry;
        });
    }

    public function updateEntry(JournalEntry $entry, array $data, array $lines): JournalEntry
    {
        return DB::transaction(function () use ($entry, $data, $lines) {
            $entry->update($data);

            $entry->lines()->delete();
            foreach ($lines as $line) {
                $entry->lines()->create($line);
            }

            $entry->recalculateTotals();

            return $entry->fresh();
        });
    }

    public function submit(JournalEntry $entry): void
    {
        if ($entry->status !== 'draft') {
            throw new \RuntimeException('Only draft entries can be submitted.');
        }

        $entry->update([
            'status'      => 'submitted',
            'odoo_status' => in_array($entry->send_to_odoo, ['No', 'Not applicable'], true)
                ? 'rejected'
                : 'pending_admin_approval',
        ]);
    }

    public function approve(JournalEntry $entry, int $approvedBy): void
    {
        if ($entry->status !== 'submitted') {
            throw new \RuntimeException('Only submitted entries can be approved.');
        }

        $entry->update([
            'status'      => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    public function reject(JournalEntry $entry, int $rejectedBy): void
    {
        if (!in_array($entry->status, ['submitted', 'draft'])) {
            throw new \RuntimeException('Entry cannot be rejected in its current state.');
        }

        $entry->update([
            'status'      => 'rejected',
            'approved_by' => $rejectedBy,
            'approved_at' => now(),
        ]);
    }

    public function post(JournalEntry $entry, int $postedBy): void
    {
        if ($entry->status !== 'approved') {
            throw new \RuntimeException('Only approved entries can be posted.');
        }

        if (!$entry->isBalanced()) {
            throw new \RuntimeException('Journal entry is not balanced (debit ≠ credit).');
        }

        $entry->update([
            'status'    => 'posted',
            'posted_by' => $postedBy,
            'posted_at' => now(),
        ]);
    }

    public function approveForOdoo(JournalEntry $entry, int $adminId): void
    {
        if (!in_array($entry->status, ['approved', 'posted'])) {
            throw new \RuntimeException('Entry must be approved or posted before authorizing Odoo export.');
        }

        if (in_array($entry->send_to_odoo, ['No', 'Not applicable'], true)) {
            throw new \RuntimeException('This entry is marked as not sendable to Odoo.');
        }

        $entry->update([
            'odoo_status'           => 'approved_for_odoo',
            'odoo_approved_by'      => $adminId,
            'odoo_approved_at'      => now(),
            'odoo_rejection_reason' => null,
        ]);
    }

    public function rejectFromOdoo(JournalEntry $entry, int $adminId, string $reason): void
    {
        $entry->update([
            'odoo_status'           => 'rejected',
            'odoo_approved_by'      => $adminId,
            'odoo_approved_at'      => now(),
            'odoo_rejection_reason' => $reason,
        ]);
    }
}
