<?php

use App\Enums\UserRole;
use App\Events\Meeting\MeetingCancelled;
use App\Events\Meeting\MeetingRescheduled;
use App\Events\Meeting\MeetingScheduled;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(fn () => $this->travelTo(now()->parse('2025-12-15 08:00:00')));

function meetingPayload(int $projectId, array $overrides = []): array
{
    return array_merge([
        'project_id' => $projectId,
        'title' => 'Design Review',
        'description' => 'The goal of this meeting is to review the homepage.',
        'date' => '2025-12-18',
        'start_time' => '10:00',
        'duration_minutes' => 30,
        'meeting_link' => 'https://meet.google.com/abc-defg-hij',
    ], $overrides);
}

it('lets the project manager schedule a meeting with guests', function () {
    Event::fake([MeetingScheduled::class]);
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project, , [$sara, $ali]] = projectWithTeam($pm);

    $this->postJson('/api/v1/meetings', meetingPayload($project->id, ['participant_ids' => [$sara->id, $ali->id]]))
        ->assertCreated()
        ->assertJsonPath('data.title', 'Design Review')
        ->assertJsonPath('data.date', '2025-12-18')
        ->assertJsonPath('data.start_time', '10:00')
        ->assertJsonPath('data.end_time', '10:30')
        ->assertJsonPath('data.duration_minutes', 30)
        ->assertJsonPath('data.state', 'upcoming')
        ->assertJsonPath('data.participants_count', 3)
        ->assertJsonPath('data.my_attendance.status.value', 'accepted')
        ->assertJsonPath('data.can.manage_content', true);

    Event::assertDispatched(MeetingScheduled::class, fn ($event) => $event->invitedIds === [$sara->id, $ali->id]);
});

it('accepts an end time instead of a duration, and a cover image', function () {
    Storage::fake('public');
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project] = projectWithTeam($pm);

    $response = $this->post('/api/v1/meetings', meetingPayload($project->id, [
        'duration_minutes' => null,
        'end_time' => '11:15',
        'cover' => UploadedFile::fake()->image('cover.jpg'),
    ]), ['Accept' => 'application/json']);

    $response->assertCreated()
        ->assertJsonPath('data.duration_minutes', 75)
        ->assertJsonPath('data.cover_url', fn (string $url) => str_contains($url, 'meeting-covers/'));

    Storage::disk('public')->assertExists(Meeting::query()->firstOrFail()->cover_path);
});

it('validates the meeting form', function (array $overrides, string $field) {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project] = projectWithTeam($pm);
    $outsider = User::factory()->create();

    $overrides = json_decode(str_replace('":outsider"', (string) $outsider->id, json_encode($overrides)), true);

    $this->postJson('/api/v1/meetings', meetingPayload($project->id, $overrides))
        ->assertStatus(422)
        ->assertJsonValidationErrors($field);
})->with([
    'no title' => [['title' => ''], 'title'],
    'in the past' => [['date' => '2025-12-14'], 'start_time'],
    'no end' => [['duration_minutes' => null], 'end_time'],
    'end before start' => [['duration_minutes' => null, 'end_time' => '09:00'], 'end_time'],
    'too long' => [['duration_minutes' => 1000], 'duration_minutes'],
    'bad link' => [['meeting_link' => 'not a link'], 'meeting_link'],
    'guest outside project' => [['participant_ids' => [':outsider']], 'participant_ids.0'],
    'unknown project' => [['project_id' => 999], 'project_id'],
]);

it('does not let team members schedule meetings', function () {
    [$project, , [$member]] = projectWithTeam();
    Sanctum::actingAs($member);

    $this->postJson('/api/v1/meetings', meetingPayload($project->id))->assertForbidden();
});

it('shows the week calendar of visible meetings, ordered by time', function () {
    [$project, , [$member]] = projectWithTeam();
    Meeting::factory()->for($project)->at('2025-12-17 12:00')->create(['title' => 'API Design Discussion']);
    Meeting::factory()->for($project)->at('2025-12-15 10:00')->create(['title' => 'Design Review']);
    Meeting::factory()->for($project)->at('2025-12-25 10:00')->create(['title' => 'Next week']);
    Meeting::factory()->create(['title' => 'Other project']);
    Sanctum::actingAs($member);

    $this->getJson('/api/v1/meetings')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.title', 'Design Review')
        ->assertJsonPath('data.1.title', 'API Design Discussion');

    $this->getJson('/api/v1/meetings?from=2025-12-22&to=2025-12-28')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Next week');
});

it('lists my upcoming meetings for the dashboard', function () {
    [$project, , [$me, $other]] = projectWithTeam();
    Meeting::factory()->for($project)->withParticipants([$me])->at('2025-12-16 09:00')->create(['title' => 'Mine']);
    Meeting::factory()->for($project)->withParticipants([$other])->at('2025-12-16 10:00')->create(['title' => 'Not mine']);
    Meeting::factory()->for($project)->withParticipants([$me])->cancelled()->create(['title' => 'Cancelled']);
    Meeting::factory()->for($project)->withParticipants([$me])->at('2025-12-14 09:00')->create(['title' => 'Past']);
    Sanctum::actingAs($me);

    $this->getJson('/api/v1/meetings?upcoming=1&mine=1&limit=3')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Mine')
        ->assertJsonPath('data.0.my_attendance.status.value', 'invited');
});

it('reschedules a meeting', function () {
    Event::fake([MeetingRescheduled::class]);
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project] = projectWithTeam($pm);
    $meeting = Meeting::factory()->for($project)->at('2025-12-18 10:00')->create();

    $this->patchJson("/api/v1/meetings/{$meeting->id}/reschedule", ['date' => '2025-12-19', 'start_time' => '14:00', 'end_time' => '15:00'])
        ->assertOk()
        ->assertJsonPath('data.date', '2025-12-19')
        ->assertJsonPath('data.start_time', '14:00')
        ->assertJsonPath('data.duration_minutes', 60);

    Event::assertDispatched(MeetingRescheduled::class, fn ($event) => $event->previousStartAt->format('Y-m-d H:i') === '2025-12-18 10:00');
});

it('updates details without touching the time', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project] = projectWithTeam($pm);
    $meeting = Meeting::factory()->for($project)->at('2025-12-18 10:00')->create();

    $this->patchJson("/api/v1/meetings/{$meeting->id}", ['title' => 'Roadmap Review - Q1', 'meeting_link' => 'https://zoom.us/j/123'])
        ->assertOk()
        ->assertJsonPath('data.title', 'Roadmap Review - Q1')
        ->assertJsonPath('data.start_time', '10:00');
});

it('cancels a meeting once, and keeps it on the calendar as cancelled', function () {
    Event::fake([MeetingCancelled::class]);
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project] = projectWithTeam($pm);
    $meeting = Meeting::factory()->for($project)->at('2025-12-16 10:00')->create();

    $this->patchJson("/api/v1/meetings/{$meeting->id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.state', 'cancelled');

    $this->patchJson("/api/v1/meetings/{$meeting->id}/cancel")->assertStatus(422);
    $this->patchJson("/api/v1/meetings/{$meeting->id}/reschedule", ['date' => '2025-12-20', 'start_time' => '10:00', 'duration_minutes' => 30])
        ->assertStatus(422);
    $this->getJson('/api/v1/meetings')->assertJsonPath('data.0.status.value', 'cancelled');

    Event::assertDispatchedTimes(MeetingCancelled::class, 1);
});

it('only lets the project manager or an admin change meetings', function () {
    [$project, , [$member]] = projectWithTeam();
    $meeting = Meeting::factory()->for($project)->withParticipants([$member])->create();
    Sanctum::actingAs($member);

    $this->patchJson("/api/v1/meetings/{$meeting->id}", ['title' => 'x'])->assertForbidden();
    $this->patchJson("/api/v1/meetings/{$meeting->id}/cancel")->assertForbidden();
    $this->deleteJson("/api/v1/meetings/{$meeting->id}")->assertForbidden();

    actingAsRole(UserRole::Admin);
    $this->deleteJson("/api/v1/meetings/{$meeting->id}")->assertOk();
    $this->assertSoftDeleted($meeting);
});

it('hides meetings from people outside the project', function () {
    $meeting = Meeting::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/v1/meetings/{$meeting->id}")->assertForbidden();
});
