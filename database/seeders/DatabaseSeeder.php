<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
        ]);

        if (app()->isLocal()) {
            $this->seedDemoUsers();
        }
    }

    /**
     * Demo accounts for local development only (password: "password").
     */
    private function seedDemoUsers(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'pm@collabspace.test'],
            User::factory()->raw(['name' => 'Mohamed Salah', 'job_title' => 'Project Manager']),
        )->syncRoles([UserRole::ProjectManager->value]);

        foreach (range(1, 5) as $i) {
            User::query()->firstOrCreate(
                ['email' => "member{$i}@collabspace.test"],
                User::factory()->raw(),
            )->syncRoles([UserRole::TeamMember->value]);
        }
    }
}
