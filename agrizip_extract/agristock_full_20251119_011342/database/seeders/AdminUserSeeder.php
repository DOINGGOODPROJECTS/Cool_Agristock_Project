<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed a default administrative user account.
     */
    public function run(): void
    {
        $email = env('ADMIN_USER_EMAIL', 'sysadmin@coolagristock.com');
        $password = env('ADMIN_USER_PASSWORD', 'Admin@12345');

        $admin = User::withTrashed()->firstOrNew(['email' => $email]);

        // Ensure soft-deleted admins are restored.
        $admin->deleted_at = null;

        $admin->fill([
            'name' => env('ADMIN_USER_NAME', 'System Administrator'),
            'username' => env('ADMIN_USER_USERNAME', 'sysadmin'),
            'phone' => env('ADMIN_USER_PHONE', '0500000000'),
            'locale' => env('ADMIN_USER_LOCALE', 'en'),
            'group_id' => 1,
        ]);

        $admin->password = Hash::make($password);
        $admin->save();
    }
}
