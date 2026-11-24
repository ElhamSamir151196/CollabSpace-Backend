<?php

use App\Enums\AttendanceStatus;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

beforeEach(fn () => $this->travelTo(now()->parse('2025-12-15 09:50')));

it('reminds participants 15 minutes before a meeting, once', function () {
    [$project, , [$going, $declined]] = projectWithTeam();
    $soon = Meeting::factory()->for($project)->withParticipants([$going, $declined])->at('2025-12-15 10:00')->create();
    $soon->participants()->updateExistingPivot($declined->id, ['attendance_status' => AttendanceStatus::Declined->value]);
    Meeting::factory()->for($project)->withParticipants([$going])->at('2025-12-15 11:00')->create(['title' => 'Later']);
    Meeting::factory()->for($project)->withParticipants([$going])->at('2025-12-15 10:05')->cancelled()->create();

    $this->artisan('meetings:send-reminders')->assertSuccessful();
    $this->artisan('meetings:send-reminders')->assertSuccessful();

    expect(notificationKinds($going))->toBe(['meeting_reminder'])
        ->and(notificationKinds($declined))->toBe([])
        ->and($soon->fresh()->reminder_sent_at)->not->toBeNull();
});

it('sends a new reminder after a meeting is rescheduled', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project, , [$member]] = projectWithTeam($pm);
    $meeting = Meeting::factory()->for($project)->withParticipants([$member])->create(['reminder_sent_at' => now()]);

    $this->patchJson("/api/v1/meetings/{$meeting->id}/reschedule", ['date' => '2025-12-16', 'start_time' => '10:00', 'duration_minutes' => 30])
        ->assertOk();

    expect($meeting->fresh()->reminder_sent_at)->toBeNull();
});

it('reminds assignees of open tasks due tomorrow, once', function () {
    [$project, $team, [$assignee]] = projectWithTeam();
    Task::factory()->for($project)->forTeam($team)->assignedTo([$assignee])->create(['due_date' => '2025-12-16', 'title' => 'Due tomorrow']);
    Task::factory()->for($project)->forTeam($team)->assignedTo([$assignee])->withStatus(TaskStatus::Completed)->create(['due_date' => '2025-12-16']);
    Task::factory()->for($project)->forTeam($team)->assignedTo([$assignee])->create(['due_date' => '2025-12-20']);

    $this->artisan('tasks:send-due-reminders')->assertSuccessful();
    $this->artisan('tasks:send-due-reminders')->assertSuccessful();

    expect(notificationKinds($assignee))->toBe(['task_due_soon'])
        ->and($assignee->notifications()->first()->data['message'])->toBe('"Due tomorrow" is due on Dec 16, 2025.');
});

it('sends a new due reminder after the due date changes', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project, $team] = projectWithTeam($pm);
    $task = Task::factory()->for($project)->forTeam($team)->create(['due_reminder_sent_at' => now()]);

    $this->patchJson("/api/v1/tasks/{$task->id}", ['due_date' => '2025-12-30'])->assertOk();

    expect($task->fresh()->due_reminder_sent_at)->toBeNull();
});

it('prunes old read notifications but keeps unread ones', function () {
    $user = User::factory()->create();
    $make = fn (?string $readAt) => DatabaseNotification::query()->create([
        'id' => (string) Str::uuid(),
        'type' => 'task_assigned',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => ['message' => 'x'],
        'read_at' => $readAt,
    ]);

    $make(now()->subDays(40)->toDateTimeString());
    $make(now()->subDays(5)->toDateTimeString());
    $make(null);

    $this->artisan('notifications:prune')->assertSuccessful();

    expect($user->notifications()->count())->toBe(2);
});
