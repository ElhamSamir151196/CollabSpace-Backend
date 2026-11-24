<?php

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\Team;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('tells everyone in a new project that they were added, except the creator', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    $team = Team::factory()->create();
    [$teamMember, $guest] = User::factory()->count(2)->create()->all();
    $team->members()->attach($teamMember->id);
    $inactive = User::factory()->inactive()->create();

    $this->postJson('/api/v1/projects', [
        'name' => 'Alpha',
        'priority' => 'high',
        'start_date' => '2026-12-01',
        'deadline' => '2027-01-31',
        'team_ids' => [$team->id],
        'guest_ids' => [$guest->id],
    ])->assertCreated();

    expect(notificationKinds($teamMember))->toBe(['added_to_project'])
        ->and(notificationKinds($guest))->toBe(['added_to_project'])
        ->and(notificationKinds($pm))->toBe([])
        ->and(notificationKinds($inactive))->toBe([]);

    $data = $guest->notifications()->first()->data;
    expect($data['message'])->toBe('You were added to the project "Alpha".')
        ->and($data['target'])->toBe(['type' => 'project', 'id' => $data['target']['id'], 'project_slug' => 'alpha'])
        ->and($data['actor']['id'])->toBe($pm->id);
});

it('notifies only newly added people when teams or guests are added', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project] = projectWithTeam($pm);
    $qa = Team::factory()->create();
    $tester = User::factory()->create();
    $qa->members()->attach($tester->id);
    $guest = User::factory()->create();

    $this->postJson("/api/v1/projects/{$project->slug}/teams", ['team_ids' => [$qa->id]])->assertOk();
    $this->postJson("/api/v1/projects/{$project->slug}/teams", ['team_ids' => [$qa->id]])->assertOk();
    $this->postJson("/api/v1/projects/{$project->slug}/guests", ['guest_ids' => [$guest->id]])->assertOk();
    $this->postJson("/api/v1/projects/{$project->slug}/guests", ['guest_ids' => [$guest->id]])->assertOk();

    expect(notificationKinds($tester))->toBe(['added_to_project'])
        ->and(notificationKinds($guest))->toBe(['added_to_project']);
});

it('notifies people added to a team, once', function () {
    actingAsRole(UserRole::Admin);
    $team = Team::factory()->create(['name' => 'Backend']);
    $user = User::factory()->create();

    $this->postJson("/api/v1/teams/{$team->id}/members", ['members' => [['user_id' => $user->id]]])->assertOk();
    $this->postJson("/api/v1/teams/{$team->id}/members", ['members' => [['user_id' => $user->id, 'position' => 'Lead']]])->assertOk();

    expect(notificationKinds($user))->toBe(['added_to_team'])
        ->and($user->notifications()->first()->data['message'])->toBe('You were added to the Backend team.');
});

it('notifies assignees of a new task, and the reviewer when it reaches review', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project, $team, [$assignee, $reviewer]] = projectWithTeam($pm);

    $taskId = $this->postJson("/api/v1/projects/{$project->slug}/tasks", [
        'title' => 'Create Wireframes',
        'team_id' => $team->id,
        'assignee_ids' => [$assignee->id],
        'reviewer_id' => $reviewer->id,
    ])->assertCreated()->json('data.id');

    expect(notificationKinds($assignee))->toBe(['task_assigned']);

    Sanctum::actingAs($assignee);
    $this->patchJson("/api/v1/tasks/{$taskId}/move", ['status' => 'in_review'])->assertOk();

    expect(notificationKinds($reviewer))->toBe(['task_status_changed'])
        ->and(notificationKinds($assignee))->toBe(['task_assigned'])
        ->and($reviewer->notifications()->first()->data['target']['status'])->toBe(TaskStatus::InReview->value);
});

it('notifies mentioned people', function () {
    [$project, , [$me, $sara]] = projectWithTeam();
    $channel = Conversation::factory()->forProject($project)->create();
    Sanctum::actingAs($me);

    $this->postJson("/api/v1/conversations/{$channel->id}/messages", [
        'body' => '@Sara please check the screen',
        'mention_ids' => [$sara->id, $me->id],
    ])->assertCreated();

    expect(notificationKinds($sara))->toBe(['mentioned'])
        ->and(notificationKinds($me))->toBe([])
        ->and($sara->notifications()->first()->data['target']['conversation_id'])->toBe($channel->id);
});

it('notifies meeting guests of the invitation, the new time and the cancellation', function () {
    $this->travelTo(now()->parse('2025-12-15 08:00'));
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project, , [$going, $declined]] = projectWithTeam($pm);

    $meetingId = $this->postJson('/api/v1/meetings', [
        'project_id' => $project->id,
        'title' => 'Design Review',
        'date' => '2025-12-18',
        'start_time' => '10:00',
        'duration_minutes' => 30,
        'participant_ids' => [$going->id, $declined->id],
    ])->assertCreated()->json('data.id');

    Sanctum::actingAs($declined);
    $this->patchJson("/api/v1/meetings/{$meetingId}/attendance", ['status' => 'declined'])->assertOk();

    Sanctum::actingAs($pm);
    $this->patchJson("/api/v1/meetings/{$meetingId}/reschedule", ['date' => '2025-12-19', 'start_time' => '11:00', 'duration_minutes' => 30])->assertOk();
    $this->patchJson("/api/v1/meetings/{$meetingId}/cancel")->assertOk();

    expect(notificationKinds($going))->toBe(['meeting_invited', 'meeting_rescheduled', 'meeting_cancelled'])
        ->and(notificationKinds($declined))->toBe(['meeting_invited'])
        ->and(notificationKinds($pm))->toBe([]);

    expect($going->notifications()->where('type', 'meeting_rescheduled')->first()->data['message'])
        ->toContain('Dec 19 at 11:00');
});
