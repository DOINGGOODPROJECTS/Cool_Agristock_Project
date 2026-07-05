<?php

namespace App\Services\Accounting;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AccountingEventRules
{
    /**
     * Return all configured event rules keyed by canonical event type.
     */
    public function all(): array
    {
        return config('accounting_events.event_types', []);
    }

    /**
     * Return canonical event types in their configured order.
     */
    public function eventTypes(): array
    {
        return array_keys($this->all());
    }

    /**
     * Return one configured event rule or fail loudly for invalid event types.
     */
    public function for(string $eventType): array
    {
        $rule = Arr::get($this->all(), $eventType);

        if ($rule === null) {
            throw new InvalidArgumentException("Unknown accounting event type [{$eventType}].");
        }

        return $rule;
    }

    public function createsAccountingEntry(string $eventType): bool
    {
        return (bool) ($this->for($eventType)['creates_accounting_entry'] ?? false);
    }

    public function classification(string $eventType): array
    {
        return $this->for($eventType)['classification'] ?? [];
    }

    public function odooModel(string $eventType): ?string
    {
        return $this->for($eventType)['odoo_model'] ?? null;
    }

    public function approvalRequired(string $eventType): bool
    {
        return (bool) ($this->for($eventType)['approval_required'] ?? false);
    }

    public function validationRules(string $eventType): array
    {
        return $this->for($eventType)['validation_rules'] ?? [];
    }

    /**
     * Build immutable IDs for future financial_events records and Odoo imports.
     */
    public function makeFinancialEventId(string $eventType, ?string $uuid = null): string
    {
        $this->for($eventType);

        $prefix = config('accounting_events.financial_event_id_prefix', 'CAG-FE');
        $eventCode = Str::of($eventType)->upper()->replace('_', '-');

        return "{$prefix}-{$eventCode}-" . ($uuid ?: (string) Str::uuid());
    }
}
