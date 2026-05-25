<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SyncPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $config = config('sync_permissions.groups');

        DB::table('sync_permissions')->truncate();

        $rows = [];
        $now  = now();

        foreach ($config as $groupId => $group) {
            foreach ($group['actions'] as $action => $allowed) {
                $rows[] = [
                    'group_id'   => $groupId,
                    'action'     => $action,
                    'allowed'    => $allowed ? 1 : 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('sync_permissions')->insert($rows);
    }
}
