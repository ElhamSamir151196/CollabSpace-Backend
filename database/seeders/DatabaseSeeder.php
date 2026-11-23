<?php

namespace Database\Seeders;

use App\Enums\Priority;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\Project;
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
            $this->seedDemoProjects();
        }
    }

    /**
     * "Alpha" and "SepetGo" from the Figma projects screen, managed by the demo PM.
     */
    private function seedDemoProjects(): void
    {
        $pm = User::query()->where('email', 'pm@collabspace.test')->first();

        if ($pm === null) {
            return;
        }

        $projects = [
            ['name' => 'Alpha', 'slug' => 'alpha', 'type' => 'SaaS', 'priority' => Priority::High, 'teams' => ['UI/UX', 'Frontend', 'Backend', 'Marketing']],
            ['name' => 'SepetGo', 'slug' => 'sepetgo', 'type' => 'Mobile App', 'priority' => Priority::Medium, 'teams' => ['UI/UX', 'Backend']],
        ];

        foreach ($projects as $data) {
            $project = Project::query()->firstOrCreate(['slug' => $data['slug']], [
                'name' => $data['name'],
                'description' => 'This project focuses on organizing tasks, tracking progress, and coordinating team efforts.',
                'type' => $data['type'],
                'status' => ProjectStatus::InProgress,
                'priority' => $data['priority'],
                'start_date' => now()->subMonth()->toDateString(),
                'deadline' => now()->addMonths(2)->toDateString(),
                'manager_id' => $pm->id,
                'created_by' => $pm->id,
            ]);

            $project->teams()->syncWithoutDetaching(
                Team::query()->whereIn('name', $data['teams'])->pluck('id')->all(),
            );
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
