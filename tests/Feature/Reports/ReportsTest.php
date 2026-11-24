<?php

use App\Enums\AttendanceStatus;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Attachment;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(fn () => $this->travelTo(now()->parse('2025-12-15 12:00')));

it('is for admins and project managers only', function () {
    [, , [$member]] = projectWithTeam();
    Sanctum::actingAs($member);

    foreach (['overview', 'projects', 'tasks', 'teams'] as $report) {
        $this->getJson("/api/v1/reports/{$report}")->assertForbidden();
    }
});

it('limits a project manager to the projects they manage', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$mine] = projectWithTeam($pm);
    $notMine = Project::factory()->create();

    $this->getJson('/api/v1/reports/projects')
        ->assertOk()
        ->assertJsonPath('data.summary.total', 1)
        ->assertJsonPath('data.projects.0.id', $mine->id);

    $this->getJson("/api/v1/reports/projects?project_id={$notMine->id}")->assertForbidden();

    actingAsRole(UserRole::Admin);
    $this->getJson('/api/v1/reports/projects')->assertJsonPath('data.summary.total', 2);
});

it('summarizes projects: active, delayed, completed and rates', function () {
    actingAsRole(UserRole::Admin);
    Project::factory()->create(['status' => ProjectStatus::InProgress]);
    Project::factory()->overdue()->create();
    Project::factory()->withStatus(ProjectStatus::Completed)->create();
    Project::factory()->withStatus(ProjectStatus::OnHold)->create();

    $this->getJson('/api/v1/reports/projects')
        ->assertOk()
        ->assertJsonPath('data.summary', [
            'total' => 4,
            'active' => 2,
            'pending' => 0,
            'on_hold' => 1,
            'completed' => 1,
            'cancelled' => 0,
            'delayed' => 1,
            'completion_rate' => 25,
        ]);
});

it('reports tasks: statuses, overdue, on-time rate and completion time', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project, $team] = projectWithTeam($pm);
    $task = fn (array $attributes) => Task::factory()->for($project)->forTeam($team)->create($attributes);

    $task(['status' => TaskStatus::Completed, 'created_at' => '2025-11-01', 'due_date' => '2025-11-10', 'completed_at' => '2025-11-05']);
    $task(['status' => TaskStatus::Completed, 'created_at' => '2025-11-01', 'due_date' => '2025-11-10', 'completed_at' => '2025-11-15']);
    $task(['status' => TaskStatus::InProgress, 'due_date' => '2025-12-01']);
    $task(['status' => TaskStatus::Pending, 'due_date' => '2025-12-31']);

    $response = $this->getJson('/api/v1/reports/tasks')->assertOk();

    $response->assertJsonPath('data.summary.total', 4)
        ->assertJsonPath('data.summary.completed', 2)
        ->assertJsonPath('data.summary.overdue', 1)
        ->assertJsonPath('data.summary.completion_rate', 50)
        ->assertJsonPath('data.summary.on_time_rate', 50)
        // (4 days + 14 days) / 2
        ->assertJsonPath('data.summary.average_days_to_complete', 9);

    $november = collect($response->json('data.completed_per_month'))->firstWhere('month', '2025-11');
    expect($november)->toBe(['month' => '2025-11', 'completed' => 2, 'on_time' => 1, 'late' => 1, 'on_time_rate' => 50]);
});

it('builds the overview cards and charts', function () {
    actingAsRole(UserRole::Admin);
    [$project, $team, [$active, $idle]] = projectWithTeam();
    Task::factory()->for($project)->forTeam($team)->assignedTo([$active])->create([
        'status' => TaskStatus::Completed, 'created_at' => '2025-12-01', 'completed_at' => '2025-12-05', 'due_date' => '2025-12-10',
    ]);
    Task::factory()->for($project)->forTeam($team)->assignedTo([$idle])->create(['created_at' => '2025-12-02']);

    $response = $this->getJson('/api/v1/reports/overview?from=2025-12-01&to=2025-12-31')->assertOk();

    $response->assertJsonPath('data.task_completion_rate.value', 50)
        ->assertJsonPath('data.team_activity_rate.value', 50)
        ->assertJsonPath('data.task_time_vs_deadline.0', ['month' => '2025-12', 'completed' => 1, 'on_time' => 1, 'late' => 0, 'on_time_rate' => 100])
        ->assertJsonPath('data.projects_progress.0', ['month' => '2025-12', 'progress' => 50])
        ->assertJsonPath('data.worker_activity.0', ['month' => '2025-12', 'completed_tasks' => 1, 'active_members' => 1])
        ->assertJsonPath('meta.period', ['from' => '2025-12-01', 'to' => '2025-12-31']);
});

it('reports performance per team', function () {
    actingAsRole(UserRole::Admin);
    [$project, $team, [$sara]] = projectWithTeam(members: 2);
    Task::factory()->for($project)->forTeam($team)->assignedTo([$sara])->count(3)->create();
    Task::factory()->for($project)->forTeam($team)->assignedTo([$sara])->withStatus(TaskStatus::Completed)->create();

    $row = collect($this->getJson('/api/v1/reports/teams')->assertOk()->json('data'))->firstWhere('id', $team->id);

    expect($row)->toMatchArray([
        'members_count' => 2,
        'active_projects' => 1,
        'assigned' => 4,
        'completed' => 1,
        'open' => 3,
        'completion_rate' => 25,
        'open_tasks_per_member' => 1.5,
    ]);
});

it('gives a personal report with a productivity score', function () {
    [$project, $team, [$me, $colleague]] = projectWithTeam();
    Task::factory()->for($project)->forTeam($team)->assignedTo([$me])->create([
        'status' => TaskStatus::Completed, 'due_date' => '2025-12-10', 'completed_at' => '2025-12-08',
    ]);
    Task::factory()->for($project)->forTeam($team)->assignedTo([$me])->create(['due_date' => '2025-12-01']);
    $attended = Meeting::factory()->for($project)->withParticipants([$me])->at('2025-12-01 10:00')->create();
    $attended->participants()->updateExistingPivot($me->id, ['attendance_status' => AttendanceStatus::Attended->value]);
    Meeting::factory()->for($project)->withParticipants([$me])->at('2025-12-02 10:00')->create();
    Attachment::factory()->forProject($project)->create(['uploaded_by' => $me->id, 'created_at' => '2025-12-03']);
    Sanctum::actingAs($me);

    $this->getJson("/api/v1/reports/users/{$me->id}")
        ->assertOk()
        ->assertJsonPath('data.tasks.assigned', 2)
        ->assertJsonPath('data.tasks.completed', 1)
        ->assertJsonPath('data.tasks.overdue', 1)
        ->assertJsonPath('data.tasks.on_time_rate', 100)
        ->assertJsonPath('data.files_uploaded', 1)
        ->assertJsonPath('data.meetings', ['invited' => 2, 'attended' => 1, 'attendance_rate' => 50])
        // 0.6 * 50 + 0.3 * 100 + 0.1 * 50 = 65
        ->assertJsonPath('data.productivity_score', 65);

    Sanctum::actingAs($colleague);
    $this->getJson("/api/v1/reports/users/{$me->id}")->assertForbidden();
});

it('lets the manager of a shared project see a member\'s report', function () {
    $pm = User::factory()->projectManager()->create();
    [, , [$member]] = projectWithTeam($pm);
    $otherPm = User::factory()->projectManager()->create();

    Sanctum::actingAs($pm);
    $this->getJson("/api/v1/reports/users/{$member->id}")->assertOk();

    Sanctum::actingAs($otherPm);
    $this->getJson("/api/v1/reports/users/{$member->id}")->assertForbidden();
});

it('validates the period', function () {
    actingAsRole(UserRole::Admin);

    $this->getJson('/api/v1/reports/overview?from=2025-12-10&to=2025-12-01')->assertStatus(422);
    $this->getJson('/api/v1/reports/overview?from=2020-01-01&to=2025-12-01')->assertStatus(422)->assertJsonValidationErrors('from');
});
