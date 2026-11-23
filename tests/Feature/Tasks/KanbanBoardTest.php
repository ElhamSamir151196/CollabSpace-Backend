<?php

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Events\TaskStatusChanged;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

function columnTitles(array $board, string $status): array
{
    $column = collect($board['data']['columns'])->firstWhere('status.value', $status);

    return collect($column['tasks'])->pluck('title')->all();
}

it('returns the four columns in order with cards sorted by position', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project, $team] = projectWithTeam($pm);
    Task::factory()->for($project)->forTeam($team)->create(['title' => 'Second', 'position' => 1]);
    Task::factory()->for($project)->forTeam($team)->create(['title' => 'First', 'position' => 0]);
    Task::factory()->for($project)->forTeam($team)->withStatus(TaskStatus::InReview)->create(['title' => 'Review me']);

    $board = $this->getJson("/api/v1/projects/{$project->slug}/board")->assertOk()->json();

    expect(collect($board['data']['columns'])->pluck('status.label')->all())
        ->toBe(['To-do', 'In Progress', 'In Review', 'Completed'])
        ->and(columnTitles($board, 'pending'))->toBe(['First', 'Second'])
        ->and(columnTitles($board, 'in_review'))->toBe(['Review me'])
        ->and($board['data']['columns'][0]['count'])->toBe(2);
});

it('filters the board to my tasks', function () {
    [$project, $team, [$me, $other]] = projectWithTeam();
    Sanctum::actingAs($me);
    Task::factory()->for($project)->forTeam($team)->assignedTo([$me])->create(['title' => 'Mine']);
    Task::factory()->for($project)->forTeam($team)->assignedTo([$other])->create(['title' => 'Theirs']);

    $board = $this->getJson("/api/v1/projects/{$project->slug}/board?mine=1")->assertOk()->json();

    expect(columnTitles($board, 'pending'))->toBe(['Mine']);
});

it('hides the board from people outside the project', function () {
    [$project] = projectWithTeam();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/v1/projects/{$project->slug}/board")->assertForbidden();
});

it('moves a card to another column at a given position and renumbers the column', function () {
    Event::fake([TaskStatusChanged::class]);
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project, $team] = projectWithTeam($pm);
    $a = Task::factory()->for($project)->forTeam($team)->withStatus(TaskStatus::InProgress)->create(['title' => 'A', 'position' => 0]);
    $b = Task::factory()->for($project)->forTeam($team)->withStatus(TaskStatus::InProgress)->create(['title' => 'B', 'position' => 1]);
    $moving = Task::factory()->for($project)->forTeam($team)->create(['title' => 'Moving']);

    $this->patchJson("/api/v1/tasks/{$moving->id}/move", ['status' => 'in_progress', 'position' => 1])
        ->assertOk()
        ->assertJsonPath('data.status.value', 'in_progress')
        ->assertJsonPath('data.position', 1);

    $board = $this->getJson("/api/v1/projects/{$project->slug}/board")->json();
    expect(columnTitles($board, 'in_progress'))->toBe(['A', 'Moving', 'B'])
        ->and($b->fresh()->position)->toBe(2);

    Event::assertDispatched(TaskStatusChanged::class, fn ($event) => $event->from === TaskStatus::Pending && $event->to === TaskStatus::InProgress);
});

it('reorders inside the same column without a status change event', function () {
    Event::fake([TaskStatusChanged::class]);
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project, $team] = projectWithTeam($pm);
    $first = Task::factory()->for($project)->forTeam($team)->create(['title' => 'First', 'position' => 0]);
    $last = Task::factory()->for($project)->forTeam($team)->create(['title' => 'Last', 'position' => 1]);

    $this->patchJson("/api/v1/tasks/{$last->id}/move", ['status' => 'pending', 'position' => 0])->assertOk();

    $board = $this->getJson("/api/v1/projects/{$project->slug}/board")->json();
    expect(columnTitles($board, 'pending'))->toBe(['Last', 'First']);
    Event::assertNotDispatched(TaskStatusChanged::class);
});

it('lets an assignee move their card up to In Review, but not complete it', function () {
    [$project, $team, [$me]] = projectWithTeam();
    $task = Task::factory()->for($project)->forTeam($team)->assignedTo([$me])->create();
    Sanctum::actingAs($me);

    $this->patchJson("/api/v1/tasks/{$task->id}/move", ['status' => 'in_progress'])->assertOk();
    $this->patchJson("/api/v1/tasks/{$task->id}/progress", ['progress' => 62])->assertOk()->assertJsonPath('data.progress', 62);
    $this->patchJson("/api/v1/tasks/{$task->id}/move", ['status' => 'in_review'])->assertOk();
    $this->patchJson("/api/v1/tasks/{$task->id}/move", ['status' => 'completed'])->assertForbidden();
});

it('lets the reviewer complete and reopen a task', function () {
    [$project, $team, [$assignee, $reviewer]] = projectWithTeam();
    $task = Task::factory()->for($project)->forTeam($team)
        ->withStatus(TaskStatus::InReview)
        ->assignedTo([$assignee])
        ->create(['reviewer_id' => $reviewer->id]);
    Sanctum::actingAs($reviewer);

    $this->patchJson("/api/v1/tasks/{$task->id}/move", ['status' => 'completed'])
        ->assertOk()
        ->assertJsonPath('data.progress', 100);

    $this->patchJson("/api/v1/tasks/{$task->id}/move", ['status' => 'in_progress'])
        ->assertOk()
        ->assertJsonPath('data.completed_at', null);

    Sanctum::actingAs($assignee);
    $task->update(['status' => TaskStatus::Completed]);
    $this->patchJson("/api/v1/tasks/{$task->id}/move", ['status' => 'in_progress'])->assertForbidden();
});

it('does not let other project members move or update cards they are not on', function () {
    [$project, $team, [$assignee, $bystander]] = projectWithTeam();
    $task = Task::factory()->for($project)->forTeam($team)->assignedTo([$assignee])->create();
    Sanctum::actingAs($bystander);

    $this->patchJson("/api/v1/tasks/{$task->id}/move", ['status' => 'in_progress'])->assertForbidden();
    $this->patchJson("/api/v1/tasks/{$task->id}/progress", ['progress' => 10])->assertForbidden();
    $this->patchJson("/api/v1/tasks/{$task->id}", ['title' => 'x'])->assertForbidden();
});

it('validates progress', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project, $team] = projectWithTeam($pm);
    $task = Task::factory()->for($project)->forTeam($team)->create();

    $this->patchJson("/api/v1/tasks/{$task->id}/progress", ['progress' => 120])
        ->assertStatus(422)
        ->assertJsonValidationErrors('progress');
});
