<?php

use App\Models\Meeting;
use App\Models\MeetingNote;
use App\Models\MeetingTranscript;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * @return array{0: Meeting, 1: User, 2: User, 3: User} meeting, organizer (PM), participant, other project member
 */
function meetingWithOrganizer(): array
{
    $pm = User::factory()->projectManager()->create();
    [$project, , [$participant, $other]] = projectWithTeam($pm);
    $meeting = Meeting::factory()->for($project)->withParticipants([$pm, $participant])->create(['created_by' => $pm->id]);

    return [$meeting, $pm, $participant, $other];
}

it('lets the organizer add transcript lines in bulk', function () {
    [$meeting, $organizer, $participant] = meetingWithOrganizer();
    Sanctum::actingAs($organizer);

    $this->postJson("/api/v1/meetings/{$meeting->id}/transcripts", ['entries' => [
        ['speaker_id' => $organizer->id, 'offset' => '00:01', 'content' => 'Good morning everyone.'],
        ['speaker_name' => 'Ahmed Hassan (guest)', 'offset' => '00:40', 'content' => 'I went through the new homepage design.'],
        ['speaker_id' => $participant->id, 'offset' => 83, 'content' => 'The layout is better.', 'language' => 'EN'],
    ]])
        ->assertCreated()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.speaker.name', $organizer->name)
        ->assertJsonPath('data.1.timestamp', '00:40')
        ->assertJsonPath('data.2.timestamp', '01:23')
        ->assertJsonPath('data.2.language', 'en');
});

it('lists the transcript in time order with search and language', function () {
    [$meeting, , $participant] = meetingWithOrganizer();
    MeetingTranscript::factory()->for($meeting)->create(['offset_seconds' => 120, 'content' => 'Sign-up flow is long', 'speaker_name' => 'Omar']);
    MeetingTranscript::factory()->for($meeting)->create(['offset_seconds' => 5, 'content' => 'Welcome', 'speaker_name' => 'Mohanad']);
    MeetingTranscript::factory()->for($meeting)->create(['offset_seconds' => 60, 'content' => 'مرحبا', 'language' => 'ar']);
    Sanctum::actingAs($participant);

    $this->getJson("/api/v1/meetings/{$meeting->id}/transcripts")
        ->assertOk()
        ->assertJsonPath('data.0.content', 'Welcome')
        ->assertJsonPath('meta.languages', ['ar', 'en']);

    $this->getJson("/api/v1/meetings/{$meeting->id}/transcripts?search=sign-up")->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/meetings/{$meeting->id}/transcripts?search=omar")->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/meetings/{$meeting->id}/transcripts?language=ar")->assertJsonCount(1, 'data');
});

it('validates transcript lines', function (array $entry, string $field) {
    [$meeting, $organizer] = meetingWithOrganizer();
    Sanctum::actingAs($organizer);

    $this->postJson("/api/v1/meetings/{$meeting->id}/transcripts", ['entries' => [$entry]])
        ->assertStatus(422)
        ->assertJsonValidationErrors($field);
})->with([
    'no speaker' => [['offset' => '00:10', 'content' => 'x'], 'entries.0.speaker_name'],
    'bad offset' => [['speaker_name' => 'A', 'offset' => '10 minutes', 'content' => 'x'], 'entries.0.offset'],
    'no content' => [['speaker_name' => 'A', 'offset' => '00:10'], 'entries.0.content'],
    'speaker outside project' => [['speaker_id' => 999999, 'offset' => '00:10', 'content' => 'x'], 'entries.0.speaker_id'],
]);

it('lets only the organizer edit or delete the transcript', function () {
    [$meeting, $organizer, $participant] = meetingWithOrganizer();
    $line = MeetingTranscript::factory()->for($meeting)->create(['offset_seconds' => 10]);

    Sanctum::actingAs($participant);
    $this->postJson("/api/v1/meetings/{$meeting->id}/transcripts", ['entries' => [['speaker_name' => 'A', 'offset' => 1, 'content' => 'x']]])->assertForbidden();
    $this->patchJson("/api/v1/meetings/{$meeting->id}/transcripts/{$line->id}", ['content' => 'x'])->assertForbidden();
    $this->deleteJson("/api/v1/meetings/{$meeting->id}/transcripts/{$line->id}")->assertForbidden();

    Sanctum::actingAs($organizer);
    $this->patchJson("/api/v1/meetings/{$meeting->id}/transcripts/{$line->id}", ['offset' => '01:00', 'content' => 'Fixed'])
        ->assertOk()
        ->assertJsonPath('data.timestamp', '01:00')
        ->assertJsonPath('data.content', 'Fixed');
    $this->deleteJson("/api/v1/meetings/{$meeting->id}/transcripts/{$line->id}")->assertOk();
});

it('returns 404 for a transcript line of another meeting', function () {
    [$meeting, $organizer] = meetingWithOrganizer();
    $foreign = MeetingTranscript::factory()->create();
    Sanctum::actingAs($organizer);

    $this->deleteJson("/api/v1/meetings/{$meeting->id}/transcripts/{$foreign->id}")->assertNotFound();
});

it('lets only the organizer change the cover image', function () {
    Storage::fake('public');
    [$meeting, $organizer, $participant] = meetingWithOrganizer();

    Sanctum::actingAs($participant);
    $this->post("/api/v1/meetings/{$meeting->id}/cover", ['cover' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json'])
        ->assertForbidden();

    Sanctum::actingAs($organizer);
    $this->post("/api/v1/meetings/{$meeting->id}/cover", ['cover' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json'])
        ->assertOk();
    $first = $meeting->fresh()->cover_path;

    $this->post("/api/v1/meetings/{$meeting->id}/cover", ['cover' => UploadedFile::fake()->image('b.png')], ['Accept' => 'application/json'])
        ->assertOk();
    Storage::disk('public')->assertMissing($first);

    $this->deleteJson("/api/v1/meetings/{$meeting->id}/cover")->assertOk()->assertJsonPath('data.cover_url', null);
});

it('lets participants write notes and comments, and edit only their own', function () {
    [$meeting, $organizer, $participant, $other] = meetingWithOrganizer();

    Sanctum::actingAs($participant);
    $noteId = $this->postJson("/api/v1/meetings/{$meeting->id}/notes", ['type' => 'note', 'body' => 'Follow-up next week'])
        ->assertCreated()
        ->assertJsonPath('data.type.value', 'note')
        ->json('data.id');
    $this->postJson("/api/v1/meetings/{$meeting->id}/notes", ['type' => 'comment', 'body' => 'Great session'])->assertCreated();

    $this->getJson("/api/v1/meetings/{$meeting->id}/notes?type=comment")
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.body', 'Great session');

    // Project member who was not invited: can read, can't write.
    Sanctum::actingAs($other);
    $this->getJson("/api/v1/meetings/{$meeting->id}/notes")->assertOk()->assertJsonCount(2, 'data');
    $this->postJson("/api/v1/meetings/{$meeting->id}/notes", ['type' => 'comment', 'body' => 'x'])->assertForbidden();

    // The organizer (manager) can't edit someone's note, but can delete it.
    Sanctum::actingAs($organizer);
    $this->patchJson("/api/v1/meetings/{$meeting->id}/notes/{$noteId}", ['body' => 'x'])->assertForbidden();

    Sanctum::actingAs($participant);
    $this->patchJson("/api/v1/meetings/{$meeting->id}/notes/{$noteId}", ['body' => 'Follow-up on Monday'])
        ->assertOk()
        ->assertJsonPath('data.is_edited', true);

    Sanctum::actingAs($organizer);
    $this->deleteJson("/api/v1/meetings/{$meeting->id}/notes/{$noteId}")->assertOk();
    expect(MeetingNote::query()->count())->toBe(1);
});

it('includes counts on the meeting page', function () {
    [$meeting, , $participant] = meetingWithOrganizer();
    MeetingTranscript::factory()->for($meeting)->count(2)->create();
    MeetingNote::factory()->for($meeting)->create();
    Sanctum::actingAs($participant);

    $this->getJson("/api/v1/meetings/{$meeting->id}")
        ->assertOk()
        ->assertJsonPath('data.transcripts_count', 2)
        ->assertJsonPath('data.notes_count', 1)
        ->assertJsonPath('data.can.manage_content', false)
        ->assertJsonPath('data.can.add_note', true);
});
