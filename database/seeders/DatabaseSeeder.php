<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Team;
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
            $this->seedDemoTeams();
        }
    }

    /**
     * Demo teams from the Figma "Teams" tab, filled with the demo members.
     */
    private function seedDemoTeams(): void
    {
        $members = User::query()->where('email', 'like', 'member%@collabspace.test')->orderBy('email')->pluck('id');

        $teams = [
            'UI/UX' => '#FCE7F3',
            'Frontend' => '#F3E8FF',
            'Backend' => '#CCFBF1',
            'Marketing' => '#F3F4F6',
        ];

        foreach (array_keys($teams) as $index => $name) {
            $team = Team::query()->firstOrCreate(['name' => $name], ['color' => $teams[$name]]);

            $team->members()->syncWithoutDetaching(
                $members->slice($index, 3)->values()->mapWithKeys(fn (int $id, int $i) => [
                    $id => ['position' => "{$name} ".($i === 0 ? 'Leader' : 'Member'), 'is_leader' => $i === 0],
                ])->all(),
            );
        }
    }

    /**
     * Demo accounts for local development only (password: "password").
     */
    private function seedDemoUsers(): void
    {
        // raw() also generates a random "email", so pass the fixed one to it too —
        // otherwise it would override the email used to find the user.
        $pmEmail = 'pm@collabspace.test';

        User::query()->firstOrCreate(
            ['email' => $pmEmail],
            User::factory()->raw(['email' => $pmEmail, 'name' => 'Mohamed Salah', 'job_title' => 'Project Manager']),
        )->syncRoles([UserRole::ProjectManager->value]);

        foreach (range(1, 5) as $i) {
            $email = "member{$i}@collabspace.test";

            User::query()->firstOrCreate(
                ['email' => $email],
                User::factory()->raw(['email' => $email]),
            )->syncRoles([UserRole::TeamMember->value]);
        }
    }
}
