<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the first `super-admin` from ADMIN_EMAIL / ADMIN_PASSWORD.
 *
 * Credentials are never hardcoded — with either variable missing the seeder
 * skips and says so, so a misconfigured environment can't silently end up with
 * a known-password admin account.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('undangyu.admin.email');
        $password = config('undangyu.admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn(
                'AdminUserSeeder skipped: set ADMIN_EMAIL and ADMIN_PASSWORD in your .env.'
            );

            return;
        }

        $admin = User::withTrashed()->firstOrNew(['email' => $email]);

        $admin->fill([
            'name' => config('undangyu.admin.name'),
            'phone' => config('undangyu.admin.phone'),
            'status' => UserStatus::Active,
        ]);

        $admin->password = Hash::make($password);
        $admin->email_verified_at ??= now();
        $admin->deleted_at = null;
        $admin->save();

        $admin->syncRoles(['super-admin']);

        $this->command?->info("Super admin ready: {$email}");
    }
}
