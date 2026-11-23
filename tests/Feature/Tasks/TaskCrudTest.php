<?php

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Events\TaskAssigned;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

it('lets the project manager create a task at the bottom of its column', function () {
    Event::fake([TaskAssigned::class]);
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project, $team, [$sara, $ali]] = projectWithTeam($pm);
    Task::factory()->for($project)->forTeam($team)->create(['position' => 0]);

    $this->postJson("/api/v1/projects/{$project->slug}/tasks", [
        'title' => 'Create Wireframes',
        'description' => 'Initial layout and structure for main page',
        'team_id' => $team->id,
        'priority' => 'high',
        'start_date' => '2026-12-01',
        'due_date' => '2026-12-12',
        'reviewer_id' => $pm->id,
        'assignee_ids' => [$sara->id, $ali->id],
    ])
        ->assertCreated()
        ->assertJsonPath('data.status', ['value' => 'pending', 'label' => 'To-do'])
        ->assertJsonPath('data.position', 1)
        ->assertJsonPath('data.team.id', $team->id)
        ->assertJsonPath('data.reviewer.id', $pm->id)
        ->assertJsonCount(2, 'data.assignees')
        ->assertJsonPath('data.can.update', true);

    Event::assertDispatched(TaskAssigned::class, fn (TaskAssigned $event) => count($event->userIds) === 2);
});

it('validates the task form', function (string $case, string $field) {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project, $team, [$member]] = projectWithTeam($pm, 1);
    $otherTeam = Team::factory()->create();
    $outsider = User::factory()->create();

    $payload = ['title' => 'Task', 'team_id' => $team->id];
    $payload = match ($case) {
        'no title' => [...$payload, 'title' => ''],
        'team not in project' => [...$payload, 'team_id' => $otherTeam->id],
        'due before start' => [...$payload, 'start_date' => '2026-12-10', 'due_date' => '2026-12-01'],
        'reviewer outside project' => [...$payload, 'reviewer_id' => $outsider->id],
        'assignee outside team' => [...$payload, 'assignee_ids' => [$outsider->id]],
        'bad status' => [...$payload, 'status' => 'done'],
    };

    $this->postJson("/api/v1/projects/{$project->slug}/tasks", $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors($field);
})->with([
    ['no title', 'title'],
    ['team not in project', 'team_id'],
    ['due before start', 'due_date'],
    ['reviewer outside project', 'reviewer_id'],
    ['assignee outside team', 'assignee_ids'],
    ['bad status', 'status'],
]);

it('does not let team members create tasks', function () {
    [$project, $team, [$member]] = projectWithTeam();
    Sanctum::actingAs($member);

    $this->postJson("/api/v1/projects/{$project->slug}/tasks", ['title' => 'x', 'team_id' => $team->id])
        ->assertForbidden();
});

it('shows a task to project members only', function () {
    [$project, $team, [$member]] = projectWithTeam();
    $task = Task::factory()->for($project)->forTeam($team)->create();

    Sanctum::actingAs($member);
    $this->getJson("/api/v1/tasks/{$task->id}")
        ->assertOk()
        ->assertJsonPath('data.project.slug', $project->slug)
        ->assertJsonPath('data.can.update', false);

    Sanctum::actingAs(User::factory()->create());
    $this->getJson("/api/v1/tasks/{$task->id}")->assertForbidden();
});

it('updates a task and syncs assignees', function () {
    Event::fake([TaskAssigned::class]);
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project, $team, [$sara, $ali]] = projectWithTeam($pm);
    $task = Task::factory()->for($project)->forTeam($team)->assignedTo([$sara])->create();

    $this->patchJson("/api/v1/tasks/{$task->id}", ['title' => 'Renamed', 'assignee_ids' => [$ali->id]])
        ->assertOk()
        ->assertJsonPath('data.title', 'Renamed')
        ->assertJsonPath('data.assignees.0.id', $ali->id)
        ->assertJsonCount(1, 'data.assignees');

    Event::assertDispatched(TaskAssigned::class, fn (TaskAssigned $event) => $event->userIds === [$ali->id]);
});

it('rejects changing the team when current assignees are not in the new team', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project, $team, [$sara]] = projectWithTeam($pm, 1);
    $newTeam = Team::factory()->create();
    $project->teams()->attach($newTeam->id);
    $task = Task::factory()->for($project)->forTeam($team)->assignedTo([$sara])->create();

    $this->patchJson("/api/v1/tasks/{$task->id}", ['team_id' => $newTeam->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors('assignee_ids');

    $this->patchJson("/api/v1/tasks/{$task->id}", ['team_id' => $newTeam->id, 'assignee_ids' => []])
        ->assertOk()
        ->assertJsonPath('data.team.id', $newTeam->id);
});

it('completing a task through update sets progress to 100 and completed_at', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project, $team] = projectWithTeam($pm);
    $task = Task::factory()->for($project)->forTeam($team)->create();

    $this->patchJson("/api/v1/tasks/{$task->id}", ['status' => 'completed'])
        ->assertOk()
        ->assertJsonPath('data.progress', 100)
        ->assertJsonPath('data.completed_at', fn ($value) => $value !== null);
});

it('soft deletes a task', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project, $team] = projectWithTeam($pm);
    $task = Task::factory()->for($project)->forTeam($team)->create();

    $this->deleteJson("/api/v1/tasks/{$task->id}")->assertOk();

    $this->assertSoftDeleted($task);
});

it('blocks unassigning a team that still has tasks in the project', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project, $team] = projectWithTeam($pm);
    Task::factory()->for($project)->forTeam($team)->create();

    $this->deleteJson("/api/v1/projects/{$project->slug}/teams/{$team->id}")
        ->assertStatus(422)
        ->assertJsonPath('message', 'This team still has tasks in the project. Move or delete them first.');
});

it('lists my tasks across projects with filters', function () {
    [$alpha, $alphaTeam, [$me, $other]] = projectWithTeam();
    [$beta, $betaTeam] = projectWithTeam();
    $betaTeam->members()->attach($me->id);
    Sanctum::actingAs($me);

    Task::factory()->for($alpha)->forTeam($alphaTeam)->assignedTo([$me])->create(['title' => 'Mine in alpha']);
    Task::factory()->for($beta)->forTeam($betaTeam)->assignedTo([$me])->overdue()->create(['title' => 'Mine late']);
    Task::factory()->for($alpha)->forTeam($alphaTeam)->assignedTo([$other])->create(['title' => 'Not mine']);
    Task::factory()->create(['title' => 'Invisible']);

    $this->getJson('/api/v1/tasks')->assertJsonCount(3, 'data')->assertJsonMissing(['title' => 'Invisible']);
    $this->getJson('/api/v1/tasks?mine=1')->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/tasks?overdue=1')->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Mine late');
    $this->getJson("/api/v1/tasks?project_id={$beta->id}")->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/projects/{$alpha->slug}/tasks?search=not")->assertJsonCount(1, 'data');
});

it('reports project progress and task stats', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project, $team] = projectWithTeam($pm);
    Task::factory()->for($project)->forTeam($team)->count(2)->create();
    Task::factory()->for($project)->forTeam($team)->withStatus(TaskStatus::InReview)->create();
    Task::factory()->for($project)->forTeam($team)->withStatus(TaskStatus::Completed)->create();

    $this->getJson("/api/v1/projects/{$project->slug}")
        ->assertOk()
        ->assertJsonPath('data.progress', 25)
        ->assertJsonPath('data.task_stats', ['pending' => 2, 'in_progress' => 0, 'in_review' => 1, 'completed' => 1, 'total' => 4]);

    $this->getJson('/api/v1/projects')
        ->assertJsonPath('data.0.progress', 25)
        ->assertJsonPath('data.0.tasks_count', 4)
        ->assertJsonMissingPath('data.0.task_stats');
});
