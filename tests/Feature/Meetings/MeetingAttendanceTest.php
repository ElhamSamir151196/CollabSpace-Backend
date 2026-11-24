<?php

use App\Enums\UserRole;
use App\Events\Meeting\MeetingParticipantsInvited;
use App\Models\Meeting;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

beforeEach(fn () => $this->travelTo(now()->parse('2025-12-15 08:00:00')));

it('invites more project members from any team', function () {
    Event::fake([MeetingParticipantsInvited::class]);
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project, , [$sara]] = projectWithTeam($pm);
    $otherTeamMember = User::factory()->create();
    $otherTeam = Team::factory()->create();
    $otherTeam->members()->attach($otherTeamMember->id);
    $project->teams()->attach($otherTeam->id);
    $meeting = Meeting::factory()->for($project)->withParticipants([$sara])->create(['created_by' => $pm->id]);

    $this->postJson("/api/v1/meetings/{$meeting->id}/participants", ['user_ids' => [$sara->id, $otherTeamMember->id]])
        ->assertOk()
        ->assertJsonCount(2, 'data');

    Event::assertDispatched(MeetingParticipantsInvited::class, fn ($event) => $event->invitedIds === [$otherTeamMember->id]);

    $this->postJson("/api/v1/meetings/{$meeting->id}/participants", ['user_ids' => [User::factory()->create()->id]])
        ->assertStatus(422);
});

it('removes a participant but never the organizer', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project, , [$sara]] = projectWithTeam($pm);
    $meeting = Meeting::factory()->for($project)->withParticipants([$pm, $sara])->create(['created_by' => $pm->id]);

    $this->deleteJson("/api/v1/meetings/{$meeting->id}/participants/{$pm->id}")->assertStatus(422);
    $this->deleteJson("/api/v1/meetings/{$meeting->id}/participants/{$sara->id}")->assertOk();

    $outsider = User::factory()->create();
    $this->deleteJson("/api/v1/meetings/{$meeting->id}/participants/{$outsider->id}")->assertNotFound();
});

it('lets participants accept or decline', function () {
    [$project, , [$me, $notInvited]] = projectWithTeam();
    $meeting = Meeting::factory()->for($project)->withParticipants([$me])->create();

    Sanctum::actingAs($me);
    $this->patchJson("/api/v1/meetings/{$meeting->id}/attendance", ['status' => 'declined'])
        ->assertOk()
        ->assertJsonPath('data.my_attendance.status.value', 'declined');

    $this->patchJson("/api/v1/meetings/{$meeting->id}/attendance", ['status' => 'attended'])->assertStatus(422);

    Sanctum::actingAs($notInvited);
    $this->patchJson("/api/v1/meetings/{$meeting->id}/attendance", ['status' => 'accepted'])->assertForbidden();
});

it('joins a live meeting, records attendance and returns the link', function () {
    [$project, , [$me]] = projectWithTeam();
    $meeting = Meeting::factory()->for($project)->withParticipants([$me])->at('2025-12-15 08:05', 30)->create([
        'meeting_link' => 'https://meet.google.com/abc',
    ]);
    Sanctum::actingAs($me);

    $this->postJson("/api/v1/meetings/{$meeting->id}/join")
        ->assertOk()
        ->assertJsonPath('data.meeting_link', 'https://meet.google.com/abc')
        ->assertJsonPath('data.my_attendance.status.value', 'attended')
        ->assertJsonPath('data.my_attendance.joined_at', fn ($value) => $value !== null);
});

it('refuses to join too early, after the end, or a cancelled meeting', function (string $start, bool $cancelled, string $message) {
    [$project, , [$me]] = projectWithTeam();
    $factory = Meeting::factory()->for($project)->withParticipants([$me])->at($start, 30);
    $meeting = ($cancelled ? $factory->cancelled() : $factory)->create();
    Sanctum::actingAs($me);

    $this->postJson("/api/v1/meetings/{$meeting->id}/join")
        ->assertStatus(422)
        ->assertJsonPath('message', fn (string $text) => str_contains($text, $message));
})->with([
    'too early' => ['2025-12-15 09:00', false, 'not started yet'],
    'ended' => ['2025-12-15 07:00', false, 'already ended'],
    'cancelled' => ['2025-12-15 08:05', true, 'cancelled'],
]);

it('lets the manager join a meeting they were not invited to', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project] = projectWithTeam($pm);
    $meeting = Meeting::factory()->for($project)->live()->create();

    $this->postJson("/api/v1/meetings/{$meeting->id}/join")->assertOk();

    expect($meeting->participants()->whereKey($pm->id)->exists())->toBeTrue();
});

it('does not let uninvited members join', function () {
    [$project, , [$member]] = projectWithTeam();
    $meeting = Meeting::factory()->for($project)->live()->create();
    Sanctum::actingAs($member);

    $this->postJson("/api/v1/meetings/{$meeting->id}/join")->assertForbidden();
});
