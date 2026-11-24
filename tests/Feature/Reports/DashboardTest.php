<?php

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Attachment;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(fn () => $this->travelTo(now()->parse('2025-12-15 12:00')));

/**
 * A project the member belongs to, with tasks spread over Nov / Dec 2025.
 *
 * @return array{0: Project, 1: User}
 */
function dashboardWorld(): array
{
    [$project, $team, [$me]] = projectWithTeam();
    $task = fn (array $attributes) => Task::factory()->for($project)->forTeam($team)->create($attributes);

    // Created last month.
    $task(['status' => TaskStatus::Pending, 'created_at' => '2025-11-10']);
    $task(['status' => TaskStatus::Completed, 'created_at' => '2025-11-05', 'completed_at' => '2025-11-20']);
    // Created this month.
    $task(['status' => TaskStatus::Pending, 'created_at' => '2025-12-02']);
    $task(['status' => TaskStatus::Pending, 'created_at' => '2025-12-03']);
    $task(['status' => TaskStatus::InProgress, 'created_at' => '2025-12-04']);
    $task(['status' => TaskStatus::Completed, 'created_at' => '2025-12-01', 'completed_at' => '2025-12-10']);
    $task(['status' => TaskStatus::Completed, 'created_at' => '2025-12-01', 'completed_at' => '2025-12-11']);

    return [$project, $me];
}

it('shows the task cards with counts, totals and trends from last month', function () {
    [, $me] = dashboardWorld();
    Sanctum::actingAs($me);

    $this->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('data.task_stats.pending', ['count' => 3, 'total' => 7, 'trend' => 100])
        ->assertJsonPath('data.task_stats.in_progress', ['count' => 1, 'total' => 7, 'trend' => null])
        ->assertJsonPath('data.task_stats.completed', ['count' => 3, 'total' => 7, 'trend' => 100])
        // 3/7 = 43% now; before December: 1 of 2 tasks = 50%.
        ->assertJsonPath('data.task_stats.completion_rate', ['value' => 43, 'previous' => 50, 'change' => -7]);
});

it('draws the yearly chart up to the current month', function () {
    [, $me] = dashboardWorld();
    Sanctum::actingAs($me);

    $chart = $this->getJson('/api/v1/dashboard')->json('data.project_overview');

    expect($chart)->toHaveCount(12)
        ->and($chart[10])->toBe(['month' => '2025-11', 'created' => 2, 'completed' => 1])
        ->and($chart[11])->toBe(['month' => '2025-12', 'created' => 5, 'completed' => 2]);
});

it('only counts projects the user can see, and refuses other projects', function () {
    [, $me] = dashboardWorld();
    $other = Project::factory()->create();
    Task::factory()->for($other)->count(5)->create();
    Sanctum::actingAs($me);

    $this->getJson('/api/v1/dashboard')->assertJsonPath('data.task_stats.pending.total', 7);
    $this->getJson("/api/v1/dashboard?project_id={$other->id}")->assertForbidden();

    actingAsRole(UserRole::Admin);
    $this->getJson('/api/v1/dashboard')->assertJsonPath('data.task_stats.pending.total', 12);
    $this->getJson("/api/v1/dashboard?project_id={$other->id}")->assertJsonPath('data.task_stats.pending.total', 5);
});

it('lists active projects, my open tasks, teams, recent files and upcoming meetings', function () {
    [$project, $team, [$me]] = projectWithTeam();
    Task::factory()->for($project)->forTeam($team)->assignedTo([$me])->create(['title' => 'Later', 'due_date' => '2025-12-30']);
    Task::factory()->for($project)->forTeam($team)->assignedTo([$me])->create(['title' => 'Sooner', 'due_date' => '2025-12-16']);
    Task::factory()->for($project)->forTeam($team)->assignedTo([$me])->withStatus(TaskStatus::Completed)->create(['title' => 'Done']);
    Task::factory()->for($project)->forTeam($team)->withStatus(TaskStatus::Completed)->create();
    Attachment::factory()->forProject($project)->create(['original_name' => 'UX_Research_Summary.pdf']);
    Meeting::factory()->for($project)->withParticipants([$me])->at('2025-12-16 10:00')->create(['title' => 'Design Review']);
    Sanctum::actingAs($me);

    $this->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('data.active_projects.0.id', $project->id)
        ->assertJsonPath('data.active_projects.0.tasks_total', 4)
        ->assertJsonPath('data.active_projects.0.tasks_completed', 2)
        ->assertJsonPath('data.active_projects.0.progress', 50)
        ->assertJsonCount(2, 'data.my_tasks')
        ->assertJsonPath('data.my_tasks.0.title', 'Sooner')
        ->assertJsonPath('data.teams.0.id', $team->id)
        ->assertJsonPath('data.teams.0.active_projects_count', 1)
        ->assertJsonPath('data.recent_files.0.name', 'UX_Research_Summary.pdf')
        ->assertJsonPath('data.upcoming_meetings.0.title', 'Design Review');
});

it('shows each team member\'s completed / assigned tasks', function () {
    [$project, $team, [$sara, $ali]] = projectWithTeam();
    Task::factory()->for($project)->forTeam($team)->assignedTo([$sara])->count(2)->create();
    Task::factory()->for($project)->forTeam($team)->assignedTo([$sara])->withStatus(TaskStatus::Completed)->create();
    Task::factory()->for($project)->forTeam($team)->assignedTo([$sara])->overdue()->create();
    Sanctum::actingAs($ali);

    $members = collect($this->getJson("/api/v1/teams/{$team->id}/workload")->assertOk()->json('data'))->keyBy('id');

    expect($members[$sara->id])->toMatchArray(['assigned' => 4, 'completed' => 1, 'open' => 3, 'overdue' => 1, 'completion_rate' => 25])
        ->and($members[$ali->id])->toMatchArray(['assigned' => 0, 'completed' => 0, 'completion_rate' => 0]);
});
