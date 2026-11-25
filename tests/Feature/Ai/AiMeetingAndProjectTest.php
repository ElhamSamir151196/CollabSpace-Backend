<?php

use App\Models\Meeting;
use App\Models\MeetingSummary;
use App\Models\User;
use App\Services\Ai\AiSettings;
use Laravel\Sanctum\Sanctum;

/**
 * @return array{0: Meeting, 1: User, 2: User} meeting, organizer (PM), participant
 */
function summaryMeeting(array $attributes = []): array
{
    $pm = User::factory()->projectManager()->create();
    [$project, , [$participant]] = projectWithTeam($pm);
    $meeting = Meeting::factory()->for($project)->withParticipants([$pm, $participant])->create(['created_by' => $pm->id, ...$attributes]);

    return [$meeting, $pm, $participant];
}

it('lets the organizer generate the meeting summary', function () {
    [$meeting, $organizer] = summaryMeeting();
    Sanctum::actingAs($organizer);

    $this->postJson("/api/v1/meetings/{$meeting->id}/summary")
        ->assertCreated()
        ->assertJsonPath('data.meeting_id', $meeting->id)
        ->assertJsonPath('data.generated_by.id', $organizer->id)
        ->assertJsonStructure(['data' => ['summary', 'key_points', 'decisions', 'action_items' => [['task', 'owner']], 'risks', 'next_steps']]);

    // Generating again replaces it.
    $this->postJson("/api/v1/meetings/{$meeting->id}/summary")->assertCreated();

    expect(MeetingSummary::query()->where('meeting_id', $meeting->id)->count())->toBe(1);
});

it('shows the summary to participants but only managers can generate it', function () {
    [$meeting, , $participant] = summaryMeeting();
    Sanctum::actingAs($participant);

    $this->getJson("/api/v1/meetings/{$meeting->id}/summary")->assertOk()->assertJsonPath('data', null);
    $this->postJson("/api/v1/meetings/{$meeting->id}/summary")->assertForbidden();

    Sanctum::actingAs(User::factory()->create());
    $this->getJson("/api/v1/meetings/{$meeting->id}/summary")->assertForbidden();
});

it('refuses to summarize an empty meeting', function () {
    [$meeting, $organizer] = summaryMeeting(['description' => null]);
    Sanctum::actingAs($organizer);

    $this->postJson("/api/v1/meetings/{$meeting->id}/summary")->assertUnprocessable();
});

it('returns 503 when the admin blocks meeting data', function () {
    [$meeting, $organizer] = summaryMeeting();
    app(AiSettings::class)->update(['allowed_modules' => ['projects', 'tasks']], User::factory()->admin()->create());
    Sanctum::actingAs($organizer);

    $this->postJson("/api/v1/meetings/{$meeting->id}/summary")->assertStatus(503);
});

it('gives project insights to members and caches them', function () {
    [$project, , [$member]] = projectWithTeam();
    Sanctum::actingAs($member);

    $first = $this->getJson("/api/v1/projects/{$project->slug}/ai-insights")
        ->assertOk()
        ->assertJsonStructure(['data' => ['summary', 'health', 'highlights', 'risks', 'recommendations', 'generated_at']])
        ->json('data.generated_at');

    $this->travel(5)->minutes();

    expect($this->getJson("/api/v1/projects/{$project->slug}/ai-insights")->json('data.generated_at'))->toBe($first)
        ->and($this->getJson("/api/v1/projects/{$project->slug}/ai-insights?refresh=1")->json('data.generated_at'))->not->toBe($first);
});

it('hides project insights from non members', function () {
    [$project] = projectWithTeam();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/v1/projects/{$project->slug}/ai-insights")->assertForbidden();
});
