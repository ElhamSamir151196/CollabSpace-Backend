<?php

use App\Enums\ConversationType;
use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\Team;

it('creates the public channel and one channel per team when a project is created', function () {
    actingAsRole(UserRole::ProjectManager);
    [$frontend, $backend] = Team::factory()->count(2)->sequence(['name' => 'Frontend'], ['name' => 'Backend'])->create()->all();

    $this->postJson('/api/v1/projects', [
        'name' => 'Alpha',
        'priority' => 'high',
        'start_date' => '2026-12-01',
        'deadline' => '2027-01-31',
        'team_ids' => [$frontend->id, $backend->id],
    ])->assertCreated();

    expect(Conversation::query()->orderBy('name')->pluck('name')->all())
        ->toBe(['Backend Team Channel', 'Frontend Team Channel', 'Public Channel']);
});

it('adds a team channel when a team is assigned later', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project] = projectWithTeam($pm);
    $qa = Team::factory()->create(['name' => 'QA']);

    $this->postJson("/api/v1/projects/{$project->slug}/teams", ['team_ids' => [$qa->id]])->assertOk();

    $this->assertDatabaseHas('conversations', [
        'type' => ConversationType::Team->value,
        'project_id' => $project->id,
        'team_id' => $qa->id,
        'name' => 'QA Team Channel',
    ]);
});

it('backfills channels for existing projects and never duplicates them', function () {
    $team = Team::factory()->create();
    Project::factory()->withTeams([$team])->count(2)->create();

    $this->artisan('chat:sync-channels')->assertSuccessful();
    $this->artisan('chat:sync-channels')->assertSuccessful();

    expect(Conversation::query()->where('type', 'project')->count())->toBe(2)
        ->and(Conversation::query()->where('type', 'team')->count())->toBe(2);
});
