<?php

namespace Database\Seeders;

use App\Enums\ConversationType;
use App\Enums\Priority;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\Chat\ProjectChannels;
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
            $this->seedDemoTasks();
            $this->seedDemoChats();
        }
    }

    /**
     * Channels for the demo projects, a few channel messages and the
     * direct chat from the Figma "Chats" screen.
     */
    private function seedDemoChats(): void
    {
        $channels = app(ProjectChannels::class);
        Project::query()->each(fn (Project $project) => $channels->ensureFor($project));

        $pm = User::query()->where('email', 'pm@collabspace.test')->first();
        $member = User::query()->where('email', 'member1@collabspace.test')->first();
        $public = Conversation::query()
            ->whereHas('project', fn ($query) => $query->where('slug', 'alpha'))
            ->where('type', ConversationType::Project->value)
            ->first();

        if ($pm === null || $member === null || $public === null) {
            return;
        }

        $this->seedMessages($public, [
            [$member, 'Yes, we can do this and edit screen'],
            [$pm, 'You should do it early'],
            [$member, 'I edit it and now you can see it'],
        ]);

        $direct = Conversation::query()->firstOrCreate(
            ['direct_key' => Conversation::directKey($pm->id, $member->id)],
            ['type' => ConversationType::Direct, 'created_by' => $pm->id],
        );
        $direct->participants()->syncWithoutDetaching([$pm->id, $member->id]);

        $this->seedMessages($direct, [
            [$member, 'Yes, we can do this and edit screen'],
            [$pm, 'Ok, I wait you'],
        ]);
    }

    /**
     * Adds the messages only if the conversation is still empty (safe to re-run).
     *
     * @param  list<array{0: User, 1: string}>  $messages
     */
    private function seedMessages(Conversation $conversation, array $messages): void
    {
        if ($conversation->messages()->exists()) {
            return;
        }

        foreach ($messages as [$author, $body]) {
            $conversation->messages()->create(['user_id' => $author->id, 'body' => $body]);
        }

        $conversation->update(['last_message_at' => now()]);
    }

    /**
     * The Kanban cards from the Figma "Tasks" screen, on the Alpha project.
     */
    private function seedDemoTasks(): void
    {
        $project = Project::query()->where('slug', 'alpha')->first();
        $team = Team::query()->where('name', 'UI/UX')->first();

        if ($project === null || $team === null) {
            return;
        }

        $members = $team->members()->pluck('users.id')->all();

        $cards = [
            ['Create Wireframes', TaskStatus::Pending, Priority::High, 0],
            ['User Research Analysis', TaskStatus::Pending, Priority::Medium, 0],
            ['Design System Updates', TaskStatus::Pending, Priority::Low, 0],
            ['Chat Screen Prototyping', TaskStatus::InProgress, Priority::High, 62],
            ['Create Miro Interaction', TaskStatus::InProgress, Priority::Low, 32],
            ['Mobile Responsiveness', TaskStatus::InReview, Priority::High, 100],
            ['Accessibility Audit', TaskStatus::InReview, Priority::Low, 100],
            ['Login Screen', TaskStatus::Completed, Priority::High, 100],
            ['Survey', TaskStatus::Completed, Priority::Low, 100],
        ];

        $positions = [];

        foreach ($cards as [$title, $status, $priority, $progress]) {
            $position = $positions[$status->value] = ($positions[$status->value] ?? -1) + 1;

            $task = Task::query()->firstOrCreate(
                ['project_id' => $project->id, 'title' => $title],
                [
                    'team_id' => $team->id,
                    'description' => 'Initial layout and structure for main page',
                    'status' => $status,
                    'priority' => $priority,
                    'progress' => $progress,
                    'position' => $position,
                    'start_date' => now()->subWeek()->toDateString(),
                    'due_date' => now()->addWeeks(2)->toDateString(),
                    'reviewer_id' => $project->manager_id,
                    'completed_at' => $status === TaskStatus::Completed ? now() : null,
                    'created_by' => $project->manager_id,
                ],
            );

            $task->assignees()->syncWithoutDetaching($members);
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
