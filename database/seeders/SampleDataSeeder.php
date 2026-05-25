<?php

namespace Database\Seeders;

use App\Models\InventoryOp;
use App\Models\InventoryStock;
use App\Models\SyncAuditLog;
use App\Models\SyncSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Storages ───────────────────────────────────────────────────
        $storageRows = [
            ['name' => 'Entrepôt Abidjan – Yopougon',  'location' => 'Yopougon, Abidjan',   'capacity' => 5000],
            ['name' => 'Entrepôt Bouaké Central',       'location' => 'Bouaké, Vallée du Bandama', 'capacity' => 3500],
            ['name' => 'Entrepôt San-Pédro – Port',     'location' => 'San-Pédro, Bas-Sassandra',  'capacity' => 8000],
            ['name' => 'Entrepôt Daloa – Marché Café',  'location' => 'Daloa, Haut-Sassandra',     'capacity' => 2000],
        ];

        $storageIds = [];
        foreach ($storageRows as $row) {
            $id = DB::table('storages')->insertGetId(array_merge($row, [
                'cooperative_id' => null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]));
            $storageIds[] = $id;
        }

        // ── 2. Users — one per missing group ─────────────────────────────
        $existingGroups = User::pluck('group_id')->unique()->toArray();
        $usersToCreate  = [
            3  => ['name' => 'Camille Kouadio',   'email' => 'comptable@agristock.test'],
            4  => ['name' => 'Adjoua Koffi',       'email' => 'caissiere@agristock.test'],
            6  => ['name' => 'Koné Pêcheries',     'email' => 'peche@agristock.test'],
            7  => ['name' => 'Grossiste Traoré',   'email' => 'grossiste@agristock.test'],
        ];
        $userByGroup = User::pluck('id', 'group_id')->toArray();
        foreach ($usersToCreate as $groupId => $data) {
            if (! in_array($groupId, $existingGroups)) {
                $uid = User::create([
                    'name'       => $data['name'],
                    'email'      => $data['email'],
                    'password'   => Hash::make('password'),
                    'group_id'   => $groupId,
                    'language'   => 'fr',
                ])->id;
                $userByGroup[$groupId] = $uid;
            }
        }

        // Pick representative user IDs per tier
        $adminId = User::where('group_id', 1)->value('id');
        $superId = User::where('group_id', 2)->value('id');
        $userId5 = User::where('group_id', 5)->value('id');
        $userId8 = User::where('group_id', 8)->value('id');

        // ── 3. Pick products from the existing set ────────────────────────
        $productIds = DB::table('products')->orderBy('id')->limit(8)->pluck('id')->toArray();
        if (empty($productIds)) {
            $this->command->warn('No products found — skipping inventory ops.');
            return;
        }

        // ── 4. Create one Stock per storage so inventory_stock has real FKs ──
        // stock_id cannot be null in inventory_stock, so we seed stock rows.
        $stockByStorage = [];
        $customerIds    = User::whereIn('group_id', [5, 6, 7, 8, 10])->pluck('id')->toArray();
        $defaultCust    = $customerIds[0] ?? $adminId;

        foreach ($storageIds as $storId) {
            $stockId = DB::table('stocks')->insertGetId([
                'ref'          => 'SEED-' . strtoupper(substr((string) Str::uuid(), 0, 8)),
                'type_storage' => 'STOCKAGE SEC',
                'qty'          => rand(5000, 20000),
                'storage_id'   => $storId,
                'customer_id'  => $customerIds[array_rand($customerIds)] ?? $defaultCust,
                'created_by'   => $adminId,
                'expired_at'   => 30,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
            $stockByStorage[$storId] = $stockId;
        }

        // ── 5. InventoryStock — seed current quantities ───────────────────
        foreach ($storageIds as $storId) {
            $stockId = $stockByStorage[$storId];
            foreach (array_slice($productIds, 0, 4) as $prodId) {
                DB::table('inventory_stock')->insertOrIgnore([
                    'storage_id'      => $storId,
                    'product_id'      => $prodId,
                    'stock_id'        => $stockId,
                    'quantity'        => rand(500, 4000),
                    'unit'            => 'kg',
                    'last_op_id'      => null,
                    'last_updated_at' => now(),
                ]);
            }
        }

        // ── 5. InventoryOps — one per status per storage ──────────────────
        $device = fn(string $suffix) => 'web-sample-' . $suffix;
        $seq    = 100; // start logical_seq above any existing

        $sessions = [];

        foreach ($storageIds as $storId) {
            $sessId = (string) Str::uuid();
            $sessions[$storId] = $sessId;

            $session = SyncSession::create([
                'session_id'         => $sessId,
                'user_id'            => $adminId,
                'device_id'          => $device('admin'),
                'ops_submitted'      => 6,
                'ops_applied'        => 3,
                'ops_conflicted'     => 1,
                'status'             => 'completed',
                'client_logical_seq' => $seq + 5,
                'created_at'         => now()->subHours(rand(1, 48)),
                'updated_at'         => now()->subHours(rand(0, 2)),
            ]);

            $prod1 = $productIds[array_rand($productIds)];
            $prod2 = $productIds[array_rand($productIds)];

            // ── applied (stock_in by admin) ───────────────────────────────
            $opApplied = InventoryOp::create([
                'op_id'              => (string) Str::uuid(),
                'user_id'            => $adminId,
                'device_id'          => $device('admin'),
                'logical_seq'        => ++$seq,
                'storage_id'         => $storId,
                'product_id'         => $prod1,
                'stock_id'           => null,
                'op_type'            => 'stock_in',
                'quantity_delta'     => rand(100, 800),
                'unit'               => 'kg',
                'notes'              => 'Entrée en stock — livraison fournisseur',
                'sync_status'        => 'applied',
                'client_created_at'  => now()->subHours(36),
                'server_received_at' => now()->subHours(35),
                'applied_at'         => now()->subHours(35),
            ]);

            // ── applied (stock_out by superviseur) ────────────────────────
            $opOut = InventoryOp::create([
                'op_id'              => (string) Str::uuid(),
                'user_id'            => $superId ?? $adminId,
                'device_id'          => $device('supervisor'),
                'logical_seq'        => ++$seq,
                'storage_id'         => $storId,
                'product_id'         => $prod2,
                'stock_id'           => null,
                'op_type'            => 'stock_out',
                'quantity_delta'     => -rand(50, 300),
                'unit'               => 'kg',
                'notes'              => 'Sortie client',
                'sync_status'        => 'applied',
                'client_created_at'  => now()->subHours(20),
                'server_received_at' => now()->subHours(19),
                'applied_at'         => now()->subHours(19),
            ]);

            // ── applied (spoilage by user) ────────────────────────────────
            InventoryOp::create([
                'op_id'              => (string) Str::uuid(),
                'user_id'            => $userId5 ?? $adminId,
                'device_id'          => $device('mobile-user5'),
                'logical_seq'        => ++$seq,
                'storage_id'         => $storId,
                'product_id'         => $prod1,
                'stock_id'           => null,
                'op_type'            => 'spoilage',
                'quantity_delta'     => -rand(10, 80),
                'unit'               => 'kg',
                'notes'              => 'Pertes constatées — chaleur',
                'sync_status'        => 'applied',
                'client_created_at'  => now()->subHours(10),
                'server_received_at' => now()->subHours(9),
                'applied_at'         => now()->subHours(9),
            ]);

            // ── pending (queued, not yet reconciled) ──────────────────────
            InventoryOp::create([
                'op_id'              => (string) Str::uuid(),
                'user_id'            => $userId8 ?? $adminId,
                'device_id'          => $device('mobile-user8'),
                'logical_seq'        => ++$seq,
                'storage_id'         => $storId,
                'product_id'         => $prod2,
                'stock_id'           => null,
                'op_type'            => 'adjustment',
                'quantity_delta'     => rand(5, 50),
                'unit'               => 'kg',
                'notes'              => 'Recomptage en attente de validation',
                'sync_status'        => 'pending',
                'client_created_at'  => now()->subMinutes(30),
                'server_received_at' => now()->subMinutes(25),
            ]);

            // ── conflict (concurrent adjustment) ─────────────────────────
            InventoryOp::create([
                'op_id'               => (string) Str::uuid(),
                'user_id'             => $userId5 ?? $adminId,
                'device_id'           => $device('mobile-user5'),
                'logical_seq'         => ++$seq,
                'storage_id'          => $storId,
                'product_id'          => $prod1,
                'stock_id'            => null,
                'op_type'             => 'adjustment',
                'quantity_delta'      => -rand(20, 100),
                'unit'                => 'kg',
                'notes'               => 'Correction hors-ligne',
                'sync_status'         => 'conflict',
                'conflict_reason'     => 'Ajustement concurrent déjà appliqué depuis la dernière synchronisation.',
                'conflict_with_op_id' => $opApplied->op_id,
                'client_created_at'   => now()->subMinutes(45),
                'server_received_at'  => now()->subMinutes(40),
            ]);

            // ── cancelled op ──────────────────────────────────────────────
            InventoryOp::create([
                'op_id'              => (string) Str::uuid(),
                'user_id'            => $superId ?? $adminId,
                'device_id'          => $device('supervisor'),
                'logical_seq'        => ++$seq,
                'storage_id'         => $storId,
                'product_id'         => $prod2,
                'stock_id'           => null,
                'op_type'            => 'adjustment',
                'quantity_delta'     => rand(1, 30),
                'unit'               => 'kg',
                'notes'              => 'Annulé — doublon saisi par erreur',
                'sync_status'        => 'cancelled',
                'cancelled_by'       => $adminId,
                'cancelled_at'       => now()->subHours(5),
                'client_created_at'  => now()->subHours(6),
                'server_received_at' => now()->subHours(6),
            ]);
        }

        // ── 6. Audit log entries ──────────────────────────────────────────
        $allOps = InventoryOp::orderBy('server_received_at')->get();

        $auditRows = [];
        foreach ($allOps as $op) {
            // submitted
            $auditRows[] = [
                'op_id'          => $op->op_id,
                'actor_id'       => $op->user_id,
                'actor_group_id' => User::find($op->user_id)?->group_id ?? 1,
                'action'         => 'submitted',
                'device_id'      => $op->device_id,
                'ip_address'     => '192.168.' . rand(1, 254) . '.' . rand(1, 254),
                'reason'         => null,
                'before_value'   => null,
                'after_value'    => json_encode(['sync_status' => 'pending', 'quantity_delta' => (float)$op->quantity_delta]),
                'created_at'     => $op->server_received_at ?? now(),
            ];

            if ($op->sync_status === 'applied') {
                $auditRows[] = [
                    'op_id'          => $op->op_id,
                    'actor_id'       => $op->user_id,
                    'actor_group_id' => User::find($op->user_id)?->group_id ?? 1,
                    'action'         => 'applied',
                    'device_id'      => $op->device_id,
                    'ip_address'     => '192.168.' . rand(1, 254) . '.' . rand(1, 254),
                    'reason'         => null,
                    'before_value'   => null,
                    'after_value'    => json_encode(['sync_status' => 'applied']),
                    'created_at'     => $op->applied_at ?? now(),
                ];
            }

            if ($op->sync_status === 'conflict') {
                $auditRows[] = [
                    'op_id'          => $op->op_id,
                    'actor_id'       => $op->user_id,
                    'actor_group_id' => User::find($op->user_id)?->group_id ?? 1,
                    'action'         => 'conflict_flagged',
                    'device_id'      => $op->device_id,
                    'ip_address'     => '192.168.' . rand(1, 254) . '.' . rand(1, 254),
                    'reason'         => null,
                    'before_value'   => null,
                    'after_value'    => json_encode(['sync_status' => 'conflict', 'reason' => $op->conflict_reason]),
                    'created_at'     => $op->server_received_at ?? now(),
                ];
            }

            if ($op->sync_status === 'cancelled') {
                $auditRows[] = [
                    'op_id'          => $op->op_id,
                    'actor_id'       => $op->cancelled_by ?? $adminId,
                    'actor_group_id' => 1,
                    'action'         => 'cancelled',
                    'device_id'      => 'web',
                    'ip_address'     => '127.0.0.1',
                    'reason'         => 'Doublon saisi par erreur',
                    'before_value'   => json_encode(['sync_status' => 'pending']),
                    'after_value'    => json_encode(['sync_status' => 'cancelled']),
                    'created_at'     => $op->cancelled_at ?? now(),
                ];
            }
        }

        // Add a couple of extra entries to show other action types
        if ($allOps->isNotEmpty()) {
            $firstOp = $allOps->first();
            $auditRows[] = [
                'op_id'          => $firstOp->op_id,
                'actor_id'       => $superId ?? $adminId,
                'actor_group_id' => 2,
                'action'         => 'edited',
                'device_id'      => 'web',
                'ip_address'     => '10.0.0.5',
                'reason'         => 'Correction de saisie initiale',
                'before_value'   => json_encode(['quantity_delta' => 100]),
                'after_value'    => json_encode(['quantity_delta' => 120]),
                'created_at'     => now()->subHours(3),
            ];
            $auditRows[] = [
                'op_id'          => $firstOp->op_id,
                'actor_id'       => $adminId,
                'actor_group_id' => 1,
                'action'         => 'reconciled',
                'device_id'      => 'web',
                'ip_address'     => '127.0.0.1',
                'reason'         => null,
                'before_value'   => null,
                'after_value'    => json_encode(['applied' => 3, 'conflict' => 1, 'already_seen' => 0]),
                'created_at'     => now()->subHours(2),
            ];
        }

        DB::table('sync_audit_log')->insert($auditRows);

        $this->command->info('✓ Storages:       ' . count($storageIds));
        $this->command->info('✓ InventoryOps:   ' . InventoryOp::count());
        $this->command->info('✓ InventoryStock: ' . InventoryStock::count());
        $this->command->info('✓ SyncSessions:   ' . SyncSession::count());
        $this->command->info('✓ AuditLog rows:  ' . count($auditRows));
    }
}
