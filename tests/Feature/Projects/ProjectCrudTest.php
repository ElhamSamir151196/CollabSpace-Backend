<?php

use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Events\ProjectCreated;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

function validProject(array $overrides = []): array
{
    return array_merge([
        'name' => 'Alpha',
        'description' => 'Organizing tasks and tracking progress.',
        'type' => 'SaaS',
        'priority' => 'high',
        'start_date' => '2026-10-12',
        'deadline' => '2026-12-25',
    ], $overrides);
}

it('lets a project manager create a project they manage, with teams and guests', function () {
    Event::fake([ProjectCreated::class]);
    $pm = actingAsRole(UserRole::ProjectManager);
    $team = Team::factory()->withMembers(2)->create();
    $guest = User::factory()->create();

    $response = $this->postJson('/api/v1/projects', validProject([
        'team_ids' => [$team->id],
        'guest_ids' => [$guest->id],
    ]));

    $response->assertCreated()
        ->assertJsonPath('data.slug', 'alpha')
        ->assertJsonPath('data.status.value', 'pending')
        ->assertJsonPath('data.priority', ['value' => 'high', 'label' => 'High'])
        ->assertJsonPath('data.deadline', '2026-12-25')
        ->assertJsonPath('data.manager.id', $pm->id)
        ->assertJsonPath('data.teams.0.id', $team->id)
        ->assertJsonPath('data.teams.0.members_count', 2)
        ->assertJsonPath('data.guests.0.id', $guest->id)
        ->assertJsonPath('data.can.update', true);

    Event::assertDispatched(ProjectCreated::class, fn (ProjectCreated $event) => $event->project->slug === 'alpha');
});

it('creates unique slugs, including for names without latin letters', function () {
    actingAsRole(UserRole::ProjectManager);

    $this->postJson('/api/v1/projects', validProject())->assertJsonPath('data.slug', 'alpha');
    $this->postJson('/api/v1/projects', validProject())->assertJsonPath('data.slug', 'alpha-2');
    $this->postJson('/api/v1/projects', validProject(['name' => 'مشروع جديد']))
        ->assertCreated()
        ->assertJsonPath('data.slug', fn (string $slug) => $slug !== '');
});

it('does not let team members create projects', function () {
    actingAsRole(UserRole::TeamMember);

    $this->postJson('/api/v1/projects', validProject())->assertForbidden();
});

it('validates the project form', function (array $overrides, string $field) {
    actingAsRole(UserRole::ProjectManager);

    $this->postJson('/api/v1/projects', validProject($overrides))
        ->assertStatus(422)
        ->assertJsonValidationErrors($field);
})->with([
    'name required' => [['name' => ''], 'name'],
    'bad priority' => [['priority' => 'urgent'], 'priority'],
    'bad status' => [['status' => 'archived'], 'status'],
    'bad date format' => [['start_date' => '12/10/2026'], 'start_date'],
    'deadline before start' => [['deadline' => '2026-10-01'], 'deadline'],
    'unknown team' => [['team_ids' => [999]], 'team_ids.0'],
    'unknown guest' => [['guest_ids' => [999]], 'guest_ids.0'],
]);

it('only lets admins pick another manager', function () {
    $otherPm = User::factory()->projectManager()->create();

    actingAsRole(UserRole::ProjectManager);
    $this->postJson('/api/v1/projects', validProject(['manager_id' => $otherPm->id]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('manager_id');

    actingAsRole(UserRole::Admin);
    $this->postJson('/api/v1/projects', validProject(['manager_id' => $otherPm->id]))
        ->assertCreated()
        ->assertJsonPath('data.manager.id', $otherPm->id);
});

it('rejects a manager who is only a team member', function () {
    actingAsRole(UserRole::Admin);
    $member = User::factory()->teamMember()->create();

    $this->postJson('/api/v1/projects', validProject(['manager_id' => $member->id]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('manager_id');
});

it('shows a project by slug to its members only', function () {
    $project = Project::factory()->create(['slug' => 'alpha']);
    $outsider = User::factory()->teamMember()->create();
    $guest = User::factory()->teamMember()->create();
    $project->guests()->attach($guest->id);

    Sanctum::actingAs($outsider);
    $this->getJson('/api/v1/projects/alpha')->assertForbidden();

    Sanctum::actingAs($guest);
    $this->getJson('/api/v1/projects/alpha')
        ->assertOk()
        ->assertJsonPath('data.id', $project->id)
        ->assertJsonPath('data.can.update', false);
});

it('updates a project and keeps completed_at in step with the status', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    $project = Project::factory()->managedBy($pm)->create();

    $this->patchJson("/api/v1/projects/{$project->slug}", ['status' => 'completed', 'name' => 'Alpha v2'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Alpha v2')
        ->assertJsonPath('data.slug', $project->slug)
        ->assertJsonPath('data.status.value', 'completed')
        ->assertJsonPath('data.completed_at', fn ($value) => $value !== null);

    $this->patchJson("/api/v1/projects/{$project->slug}", ['status' => 'in_progress'])
        ->assertOk()
        ->assertJsonPath('data.completed_at', null);
});

it('checks the deadline against the saved start date on partial updates', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    $project = Project::factory()->managedBy($pm)->create(['start_date' => '2026-10-12', 'deadline' => '2026-12-25']);

    $this->patchJson("/api/v1/projects/{$project->slug}", ['deadline' => '2026-10-01'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('deadline');
});

it('does not let a project manager edit or delete a project managed by someone else', function () {
    actingAsRole(UserRole::ProjectManager);
    $project = Project::factory()->create();

    $this->patchJson("/api/v1/projects/{$project->slug}", ['name' => 'Hijack'])->assertForbidden();
    $this->deleteJson("/api/v1/projects/{$project->slug}")->assertForbidden();
});

it('lets the manager soft delete the project', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    $project = Project::factory()->managedBy($pm)->create();

    $this->deleteJson("/api/v1/projects/{$project->slug}")->assertOk();

    $this->assertSoftDeleted($project);
    $this->getJson("/api/v1/projects/{$project->slug}")->assertNotFound();
});

it('lets an admin manage any project', function () {
    actingAsRole(UserRole::Admin);
    $project = Project::factory()->withStatus(ProjectStatus::OnHold)->create();

    $this->patchJson("/api/v1/projects/{$project->slug}", ['status' => 'in_progress'])->assertOk();
    $this->deleteJson("/api/v1/projects/{$project->slug}")->assertOk();
});
