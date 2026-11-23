<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the first admin from ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD in .env.
 * Change the password right after the first login on any shared environment.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('collabspace.admin.email');
        $password = (string) config('collabspace.admin.password');

        if ($email === '' || $password === '') {
            $this->command->warn('ADMIN_EMAIL / ADMIN_PASSWORD are not set — skipping admin user.');

            return;
        }

        $admin = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => config('collabspace.admin.name'),
                'password' => $password,
                'email_verified_at' => now(),
                'job_title' => 'System Administrator',
            ],
        );

        $admin->syncRoles([UserRole::Admin->value]);
    }
}
