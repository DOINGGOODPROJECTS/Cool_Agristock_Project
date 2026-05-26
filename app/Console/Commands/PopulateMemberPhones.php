<?php

namespace App\Console\Commands;

use App\Models\MemberPhone;
use App\Models\User;
use Illuminate\Console\Command;

class PopulateMemberPhones extends Command
{
    protected $signature   = 'phones:populate {--commit : Actually write to DB (default is dry-run)}';
    protected $description = "Populate member_phones from users.phone (Côte d'Ivoire E.164 normalisation)";

    public function handle(): int
    {
        $commit  = $this->option('commit');
        $users   = User::whereNotNull('phone')->where('phone', '!=', '')->get(['id', 'name', 'group_id', 'phone']);
        $existing = MemberPhone::pluck('user_id')->flip();

        $rows    = [];
        $skipped = [];

        foreach ($users as $user) {
            $e164 = $this->normalisePhone($user->phone);

            if ($e164 === null) {
                $skipped[] = "  SKIP  uid={$user->id} name={$user->name} raw={$user->phone} → could not normalise";
                continue;
            }

            if (isset($existing[$user->id])) {
                $skipped[] = "  SKIP  uid={$user->id} name={$user->name} → already in member_phones";
                continue;
            }

            $rows[] = ['user_id' => $user->id, 'name' => $user->name, 'phone' => $e164];
        }

        $this->table(['user_id', 'name', 'phone'], $rows);

        if (count($skipped)) {
            $this->line('');
            foreach ($skipped as $line) {
                $this->line($line);
            }
        }

        $this->line('');
        $this->info(count($rows) . ' row(s) to insert, ' . count($skipped) . ' skipped.');

        if (! $commit) {
            $this->warn('Dry-run — pass --commit to write.');
            return 0;
        }

        foreach ($rows as $row) {
            MemberPhone::firstOrCreate(
                ['user_id' => $row['user_id']],
                ['phone' => $row['phone'], 'verified_at' => now()]
            );
        }

        $this->info('Done.');
        return 0;
    }

    private function normalisePhone(string $raw): ?string
    {
        $p = preg_replace('/[\s\-().]+/', '', $raw);

        if ($p === '' || $p === null) {
            return null;
        }

        // Already E.164
        if (str_starts_with($p, '+')) {
            return strlen($p) >= 10 ? $p : null;
        }

        // International without +
        if (str_starts_with($p, '00')) {
            $p = '+' . substr($p, 2);
            return strlen($p) >= 10 ? $p : null;
        }

        // Ivorian local: 0XXXXXXXXX (10 digits) → +2250XXXXXXXXX
        if (strlen($p) === 10 && str_starts_with($p, '0')) {
            return '+225' . $p;
        }

        // Ivorian local without leading 0: 9 digits → +2259XXXXXXXX or similar
        if (strlen($p) === 9) {
            return '+225' . $p;
        }

        // Country code already present (225XXXXXXXXX, 10+ digits starting with country code)
        if (str_starts_with($p, '225') && strlen($p) >= 12) {
            return '+' . $p;
        }

        return null;
    }
}
