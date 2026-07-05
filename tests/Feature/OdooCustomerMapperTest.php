<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Odoo\OdooCustomerMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OdooCustomerMapperTest extends TestCase
{
    use RefreshDatabase;

    private OdooCustomerMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new OdooCustomerMapper();
    }

    // -------------------------------------------------------------------------
    // CUS-02: External ID format
    // -------------------------------------------------------------------------

    public function test_external_id_uses_canonical_prefix(): void
    {
        $this->assertSame('coolagristock.customer.42', $this->mapper->externalId(42));
        $this->assertSame('coolagristock.customer.1',  $this->mapper->externalId(1));
    }

    // -------------------------------------------------------------------------
    // CUS-03: Field mapping
    // -------------------------------------------------------------------------

    public function test_to_partner_maps_user_fields_correctly(): void
    {
        $user = $this->makeUser(['id' => 10, 'name' => 'Amara Diallo', 'phone' => '0244000001', 'email' => 'amara@farm.gh']);

        $partner = $this->mapper->toPartner($user);

        $this->assertSame('coolagristock.customer.10', $partner['id']);
        $this->assertSame('Amara Diallo', $partner['name']);
        $this->assertSame('0244000001', $partner['phone']);
        $this->assertSame('amara@farm.gh', $partner['email']);
        $this->assertSame(1, $partner['customer_rank']);
        $this->assertSame(0, $partner['supplier_rank']);
    }

    public function test_to_partner_returns_null_when_name_is_missing(): void
    {
        $user = $this->makeUser(['name' => '']);

        $this->assertNull($this->mapper->toPartner($user));
    }

    public function test_to_partner_company_type_is_person_for_farmer(): void
    {
        $user    = $this->makeUser(['name' => 'Kofi Mensah']);
        $partner = $this->mapper->toPartner($user);

        $this->assertSame('person', $partner['company_type']);
    }

    // -------------------------------------------------------------------------
    // CUS-01: CSV headers match Odoo res.partner import columns
    // -------------------------------------------------------------------------

    public function test_csv_headers_contain_required_odoo_columns(): void
    {
        $headers = $this->mapper->csvHeaders();

        foreach (['id', 'name', 'phone', 'email', 'customer_rank', 'company_type', 'ref'] as $col) {
            $this->assertContains($col, $headers, "CSV header [{$col}] is missing.");
        }
    }

    public function test_to_csv_row_strips_internal_metadata_keys(): void
    {
        $user   = $this->makeUser(['name' => 'Ama Boateng', 'phone' => '0201234567']);
        $partner = $this->mapper->toPartner($user);
        $row     = $this->mapper->toCsvRow($partner);

        $this->assertArrayNotHasKey('_source_id',    $row);
        $this->assertArrayNotHasKey('_source_phone', $row);
        $this->assertArrayNotHasKey('_source_email', $row);
        $this->assertArrayHasKey('name', $row);
        $this->assertArrayHasKey('id',   $row);
    }

    // -------------------------------------------------------------------------
    // CUS-04: Duplicate detection
    // -------------------------------------------------------------------------

    public function test_detect_duplicates_flags_phone_conflict(): void
    {
        $user1 = $this->makeUser(['name' => 'Kwame Asante', 'phone' => '0550123456', 'email' => 'kwame@farm.gh']);
        $user2 = $this->makeUser(['name' => 'Kwame Clone',  'phone' => '0550123456', 'email' => 'clone@farm.gh']);

        $signals = $this->mapper->detectDuplicates($user1);

        $this->assertContains('phone_conflict', $signals);
    }

    public function test_detect_duplicates_returns_empty_for_unique_customer(): void
    {
        $user = $this->makeUser(['name' => 'Unique Farmer', 'phone' => '0209999999', 'email' => 'unique@farm.gh']);

        $signals = $this->mapper->detectDuplicates($user);

        $this->assertEmpty($signals);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeUser(array $attributes = []): User
    {
        $defaults = [
            'name'     => 'Test Farmer',
            'email'    => 'test' . uniqid() . '@farm.gh',
            'phone'    => '024' . rand(1000000, 9999999),
            'password' => bcrypt('secret'),
            'group_id' => 4, // default customer group
        ];

        return User::create(array_merge($defaults, $attributes));
    }
}
