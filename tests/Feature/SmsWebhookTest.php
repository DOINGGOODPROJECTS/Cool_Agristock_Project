<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MemberPhone;
use App\Models\InventoryOp;
use App\Models\SyncPermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

/**
 * S14-13 → S14-21: SMS webhook end-to-end tests.
 *
 * Uses the first Storage (S1) and first Product already in the DB so the
 * SmsParser can resolve them without creating extra fixtures.
 *
 * AT API calls are suppressed by setting services.africastalking.api_key
 * to 'sandbox' in setUp() — the SmsController reply() method takes an
 * early-exit path for that value (log-only, no real HTTP call).
 */
class SmsWebhookTest extends TestCase
{
    use DatabaseTransactions;

    private User   $member;
    private string $phone      = '+2250799000001';
    private string $product;   // actual name from DB
    private string $smsProduct; // normalised name for SMS

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();

        // Suppress real AT SDK calls in all tests
        config(['services.africastalking.api_key' => 'sandbox']);
        config(['services.africastalking.webhook_key' => null]); // skip sig by default

        // Use the first product already in the DB
        $prod              = \App\Models\Product::orderBy('id')->first();
        $this->product     = $prod->name;
        $this->smsProduct  = $prod->name; // SmsParser does fuzzy match

        // Create a user and register their phone
        $this->member = User::factory()->create([
            'group_id' => 5,
            'phone'    => '0799000001',
        ]);

        MemberPhone::firstOrCreate(
            ['phone'   => $this->phone],
            ['user_id' => $this->member->id, 'verified_at' => now()]
        );
    }

    // ── S14-19: Route requires no authentication ──────────────────────────

    public function test_webhook_route_is_accessible_without_authentication(): void
    {
        // No actingAs() — unauthenticated POST must still reach the controller
        $this->post('/webhook/sms', $this->smsPayload("ENTREE 10 kg {$this->smsProduct} S1"))
             ->assertStatus(200)
             ->assertSee('OK');
    }

    // ── S14-13: AT signature verification ────────────────────────────────

    public function test_invalid_signature_returns_401_when_key_is_configured(): void
    {
        config(['services.africastalking.webhook_key' => 'secret-key']);

        $this->post('/webhook/sms',
            $this->smsPayload("ENTREE 10 kg {$this->smsProduct} S1"),
            ['X-AT-Checksum' => 'definitely-wrong']
        )->assertStatus(401);
    }

    public function test_valid_hmac_signature_passes_verification(): void
    {
        $secret  = 'test-hmac-secret';
        $params  = $this->smsPayload("ENTREE 10 kg {$this->smsProduct} S1");
        $rawBody = http_build_query($params);
        $sig     = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));

        config(['services.africastalking.webhook_key' => $secret]);

        $this->call(
            'POST', '/webhook/sms', [], [], [],
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded',
             'HTTP_X-AT-Checksum' => $sig],
            $rawBody
        )->assertStatus(200);
    }

    public function test_missing_signature_header_returns_401_when_key_configured(): void
    {
        config(['services.africastalking.webhook_key' => 'secret-key']);

        $this->post('/webhook/sms', $this->smsPayload("ENTREE 10 kg {$this->smsProduct} S1"))
             ->assertStatus(401);
    }

    // ── S14-14: User lookup via member_phones ─────────────────────────────

    public function test_unregistered_phone_returns_200_and_creates_no_op(): void
    {
        $unknown = '+2250000099999';

        $this->post('/webhook/sms', $this->smsPayload("ENTREE 10 kg {$this->smsProduct} S1", $unknown))
             ->assertStatus(200)->assertSee('OK');

        $this->assertDatabaseMissing('inventory_ops', ['device_id' => "sms:{$unknown}"]);
    }

    // ── S14-15: sync.push permission gate ────────────────────────────────

    public function test_user_without_push_permission_is_denied_and_no_op_created(): void
    {
        // Create a temporary group and deny sync.push for it
        $group = \App\Models\Group::create(['name' => 'No Push Group']);
        SyncPermission::updateOrCreate(
            ['group_id' => $group->id, 'action' => 'sync.push'],
            ['allowed'  => false]
        );

        $blockedPhone = '+2250000077777';
        $blocked      = User::factory()->create(['group_id' => $group->id]);
        MemberPhone::create([
            'phone'       => $blockedPhone,
            'user_id'     => $blocked->id,
            'verified_at' => now(),
        ]);

        $this->post('/webhook/sms', $this->smsPayload("ENTREE 10 kg {$this->smsProduct} S1", $blockedPhone))
             ->assertStatus(200);

        $this->assertDatabaseMissing('inventory_ops', ['device_id' => "sms:{$blockedPhone}"]);
    }

    // ── S14-05: ENTREE → stock_in ─────────────────────────────────────────

    public function test_entree_sms_creates_stock_in_op_with_correct_delta(): void
    {
        $this->post('/webhook/sms', $this->smsPayload("ENTREE 45 kg {$this->smsProduct} S1"))
             ->assertStatus(200);

        $op = InventoryOp::where('device_id', "sms:{$this->phone}")
                         ->where('op_type', 'stock_in')
                         ->first();

        $this->assertNotNull($op, 'stock_in op should be created');
        $this->assertEquals(45, (float) $op->quantity_delta);
        $this->assertEquals('kg', $op->unit);
    }

    // ── S14-06: SORTIE → stock_out (negative delta) ───────────────────────

    public function test_sortie_sms_creates_stock_out_op_with_negative_delta(): void
    {
        $this->post('/webhook/sms', $this->smsPayload("SORTIE 12 kg {$this->smsProduct} S1"))
             ->assertStatus(200);

        $op = InventoryOp::where('device_id', "sms:{$this->phone}")
                         ->where('op_type', 'stock_out')
                         ->first();

        $this->assertNotNull($op);
        $this->assertLessThan(0, (float) $op->quantity_delta, 'delta must be negative for SORTIE');
        $this->assertEquals(-12, (float) $op->quantity_delta);
    }

    // ── S14-07: POURRI → spoilage ─────────────────────────────────────────

    public function test_pourri_sms_creates_spoilage_op(): void
    {
        $this->post('/webhook/sms', $this->smsPayload("POURRI 8 kg {$this->smsProduct} S1"))
             ->assertStatus(200);

        $op = InventoryOp::where('device_id', "sms:{$this->phone}")
                         ->where('op_type', 'spoilage')
                         ->first();

        $this->assertNotNull($op);
        $this->assertEquals(-8, (float) $op->quantity_delta);
    }

    // ── S14-08: AJUSTER → adjustment ─────────────────────────────────────

    public function test_ajuster_sms_creates_adjustment_op(): void
    {
        $this->post('/webhook/sms', $this->smsPayload("AJUSTER 150 kg {$this->smsProduct} S1"))
             ->assertStatus(200);

        $op = InventoryOp::where('device_id', "sms:{$this->phone}")
                         ->where('op_type', 'adjustment')
                         ->first();

        $this->assertNotNull($op);
        $this->assertEquals(150, (float) $op->quantity_delta);
    }

    // ── S14-09: Unrecognised format → no op created ───────────────────────

    public function test_unrecognised_sms_format_creates_no_op(): void
    {
        $before = InventoryOp::where('device_id', "sms:{$this->phone}")->count();

        $this->post('/webhook/sms', $this->smsPayload('BONJOUR comment ça va ?'))
             ->assertStatus(200);

        $after = InventoryOp::where('device_id', "sms:{$this->phone}")->count();
        $this->assertEquals($before, $after, 'Invalid SMS must not create an op');
    }

    // ── S14-17: Audit log entry created ──────────────────────────────────

    public function test_submitted_action_is_written_to_audit_log(): void
    {
        $this->post('/webhook/sms', $this->smsPayload("ENTREE 20 kg {$this->smsProduct} S1"))
             ->assertStatus(200);

        $op = InventoryOp::where('device_id', "sms:{$this->phone}")->get()->last();
        $this->assertNotNull($op);

        $this->assertDatabaseHas('sync_audit_log', [
            'op_id'  => $op->op_id,
            'action' => 'submitted',
        ]);
    }

    // ── S14-18: French reply (device_id set correctly) ───────────────────

    public function test_sms_op_device_id_is_prefixed_with_sms_and_phone(): void
    {
        $this->post('/webhook/sms', $this->smsPayload("ENTREE 30 kg {$this->smsProduct} S1"))
             ->assertStatus(200);

        $this->assertDatabaseHas('inventory_ops', [
            'device_id' => "sms:{$this->phone}",
            'user_id'   => $this->member->id,
        ]);
    }

    // ── S14-21: Full end-to-end ───────────────────────────────────────────

    public function test_full_end_to_end_sms_to_inventory_op_and_audit_log(): void
    {
        $this->post('/webhook/sms', $this->smsPayload("ENTREE 50 kg {$this->smsProduct} S1"))
             ->assertStatus(200)->assertSee('OK');

        // Op recorded
        $op = InventoryOp::where('device_id', "sms:{$this->phone}")
                         ->where('op_type', 'stock_in')
                         ->first();

        $this->assertNotNull($op, 'inventory_ops row must exist');
        $this->assertEquals(50, (float) $op->quantity_delta);
        $this->assertEquals('kg', $op->unit);
        $this->assertEquals($this->member->id, $op->user_id);
        $this->assertNotNull($op->op_id);

        // Audit log entry
        $this->assertDatabaseHas('sync_audit_log', [
            'op_id'  => $op->op_id,
            'action' => 'submitted',
        ]);

        // Op is either applied or pending (engine decides based on stock state)
        $this->assertContains($op->sync_status, ['applied', 'pending', 'conflict']);
    }

    // ── Helper ────────────────────────────────────────────────────────────

    private function smsPayload(string $text, string $from = null): array
    {
        return [
            'from' => $from ?? $this->phone,
            'to'   => '20098',
            'text' => $text,
            'date' => now()->toIso8601String(),
            'id'   => 'AT' . Str::random(8),
        ];
    }

    private function seedPermissions(): void
    {
        $config = config('sync_permissions.groups');
        foreach ($config as $groupId => $group) {
            foreach ($group['actions'] as $action => $allowed) {
                SyncPermission::updateOrCreate(
                    ['group_id' => $groupId, 'action' => $action],
                    ['allowed'  => $allowed]
                );
            }
        }
    }
}
