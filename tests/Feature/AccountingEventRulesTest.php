<?php

namespace Tests\Feature;

use App\Services\Accounting\AccountingEventRules;
use InvalidArgumentException;
use Tests\TestCase;

class AccountingEventRulesTest extends TestCase
{
    private AccountingEventRules $rules;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rules = new AccountingEventRules();
    }

    public function test_it_defines_tracker_1_0_event_taxonomy(): void
    {
        $this->assertSame([
            'product_deposit',
            'storage_fee',
            'drying_fee',
            'handling_fee',
            'advance_payment',
            'payment_received',
            'withdrawal',
            'spoilage_customer_fault',
            'spoilage_cool_agristock_fault',
            'claim',
            'credit_note',
            'refund',
        ], $this->rules->eventTypes());
    }

    public function test_product_deposit_is_custody_only_with_no_odoo_accounting_model(): void
    {
        $this->assertFalse($this->rules->createsAccountingEntry('product_deposit'));
        $this->assertSame(['custody_stock'], $this->rules->classification('product_deposit'));
        $this->assertNull($this->rules->odooModel('product_deposit'));
    }

    public function test_chargeable_service_fees_create_invoice_revenue_and_receivable(): void
    {
        foreach (['storage_fee', 'drying_fee', 'handling_fee'] as $eventType) {
            $rule = $this->rules->for($eventType);

            $this->assertTrue($this->rules->createsAccountingEntry($eventType));
            $this->assertContains('revenue', $rule['classification']);
            $this->assertContains('receivable', $rule['classification']);
            $this->assertSame('accounts_receivable', $rule['debit']);
            $this->assertSame('account.move', $this->rules->odooModel($eventType));
        }
    }

    public function test_payments_distinguish_advance_liability_from_receivable_settlement(): void
    {
        $advance = $this->rules->for('advance_payment');
        $payment = $this->rules->for('payment_received');

        $this->assertContains('liability', $advance['classification']);
        $this->assertSame('customer_advances', $advance['credit']);
        $this->assertContains('receivable_settlement', $payment['classification']);
        $this->assertSame('accounts_receivable', $payment['credit']);
    }

    public function test_claims_credit_notes_refunds_and_company_fault_spoilage_require_approval(): void
    {
        foreach (['spoilage_cool_agristock_fault', 'claim', 'credit_note', 'refund'] as $eventType) {
            $this->assertTrue($this->rules->approvalRequired($eventType));
            $this->assertNotEmpty($this->rules->validationRules($eventType));
        }
    }

    public function test_financial_event_id_uses_canonical_prefix_and_event_type(): void
    {
        $id = $this->rules->makeFinancialEventId(
            'storage_fee',
            '550e8400-e29b-41d4-a716-446655440000'
        );

        $this->assertSame('CAG-FE-STORAGE-FEE-550e8400-e29b-41d4-a716-446655440000', $id);
    }

    public function test_unknown_event_type_fails_fast(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->rules->for('unsupported_event');
    }
}
