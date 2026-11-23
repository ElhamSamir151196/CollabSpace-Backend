<?php

use App\Enums\UserRole;
use App\Models\Attachment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(fn () => Storage::fake('local'));

/**
 * Uploads one real (fake) file through the API and returns the created row + response data.
 *
 * @return array{0: Attachment, 1: array<string, mixed>}
 */
function uploadTo(string $url, UploadedFile $file): array
{
    $data = test()->post($url, ['files' => [$file]])->assertCreated()->json('data.0');

    return [Attachment::query()->findOrFail($data['id']), $data];
}

it('downloads through the signed link without a token', function () {
    [$project, $team, [$member]] = projectWithTeam();
    Sanctum::actingAs($member);
    [, $data] = uploadTo("/api/v1/projects/{$project->slug}/files", UploadedFile::fake()->createWithContent('notes.txt', 'hello team'));

    $this->app['auth']->forgetGuards();

    $response = $this->get($data['download_url']);
    $response->assertOk();

    expect($response->streamedContent())->toBe('hello team')
        ->and($response->headers->get('Content-Disposition'))->toContain('attachment')
        ->and($response->headers->get('Content-Disposition'))->toContain('notes.txt')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
});

it('shows images inline with the preview link', function () {
    [$project, $team, [$member]] = projectWithTeam();
    Sanctum::actingAs($member);
    [, $data] = uploadTo("/api/v1/projects/{$project->slug}/files", UploadedFile::fake()->image('photo.png'));

    expect($this->get($data['preview_url'])->assertOk()->headers->get('Content-Disposition'))->toContain('inline');
});

it('rejects tampered or expired links', function () {
    [$project, $team, [$member]] = projectWithTeam();
    Sanctum::actingAs($member);
    [$first, $data] = uploadTo("/api/v1/projects/{$project->slug}/files", UploadedFile::fake()->image('a.png'));
    [$second] = uploadTo("/api/v1/projects/{$project->slug}/files", UploadedFile::fake()->image('b.png'));

    // Same signature, another file id.
    $tampered = str_replace("/files/{$first->id}/", "/files/{$second->id}/", $data['download_url']);
    $this->get($tampered)->assertForbidden();

    $this->travel(31)->minutes();
    $this->get($data['download_url'])->assertForbidden();
});

it('lists project and task files together on the project Files tab, with filters', function () {
    [$project, $team, [$member]] = projectWithTeam();
    $task = Task::factory()->for($project)->forTeam($team)->create();
    Sanctum::actingAs($member);

    uploadTo("/api/v1/projects/{$project->slug}/files", UploadedFile::fake()->create('spec.pdf', 10, 'application/pdf'));
    uploadTo("/api/v1/tasks/{$task->id}/files", UploadedFile::fake()->image('screen.png'));

    $this->getJson("/api/v1/projects/{$project->slug}/files")->assertJsonCount(2, 'data');
    $this->getJson("/api/v1/projects/{$project->slug}/files?type=image")->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'screen.png');
    $this->getJson("/api/v1/projects/{$project->slug}/files?source=project")->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'spec.pdf');
    $this->getJson("/api/v1/projects/{$project->slug}/files?search=spec")->assertJsonCount(1, 'data');
});

it('lets the uploader and the project manager delete a file, but not other members', function () {
    $pm = User::factory()->projectManager()->create();
    [$project, $team, [$uploader, $other]] = projectWithTeam($pm);

    Sanctum::actingAs($uploader);
    [$mine] = uploadTo("/api/v1/projects/{$project->slug}/files", UploadedFile::fake()->image('mine.png'));
    [$second] = uploadTo("/api/v1/projects/{$project->slug}/files", UploadedFile::fake()->image('second.png'));

    Sanctum::actingAs($other);
    $this->getJson("/api/v1/files/{$mine->id}")->assertOk()->assertJsonPath('data.can.delete', false);
    $this->deleteJson("/api/v1/files/{$mine->id}")->assertForbidden();

    Sanctum::actingAs($uploader);
    $this->deleteJson("/api/v1/files/{$mine->id}")->assertOk();
    Storage::disk('local')->assertMissing($mine->path);
    $this->assertDatabaseMissing('attachments', ['id' => $mine->id]);

    Sanctum::actingAs($pm);
    $this->deleteJson("/api/v1/files/{$second->id}")->assertOk();
    $this->assertDatabaseHas('activity_log', ['log_name' => 'files', 'event' => 'deleted']);
});

it('hides a file from people outside the project', function () {
    [$project, $team, [$member]] = projectWithTeam();
    Sanctum::actingAs($member);
    [$file] = uploadTo("/api/v1/projects/{$project->slug}/files", UploadedFile::fake()->image('x.png'));

    Sanctum::actingAs(User::factory()->create());
    $this->getJson("/api/v1/files/{$file->id}")->assertForbidden();
    $this->deleteJson("/api/v1/files/{$file->id}")->assertForbidden();
});

it('shows recent files only from visible projects', function () {
    [$mine, $team, [$me]] = projectWithTeam();
    [$other] = projectWithTeam();
    Attachment::factory()->forProject($mine)->count(3)->create();
    Attachment::factory()->forProject($other)->create(['original_name' => 'secret.pdf']);
    Sanctum::actingAs($me);

    $this->getJson('/api/v1/files/recent?limit=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonMissing(['name' => 'secret.pdf']);
});

it('lists my uploaded files on the profile', function () {
    [$project, $team, [$me]] = projectWithTeam();
    Attachment::factory()->forProject($project)->create(['uploaded_by' => $me->id, 'original_name' => 'mine.pdf']);
    Attachment::factory()->forProject($project)->create(['original_name' => 'theirs.pdf']);
    Sanctum::actingAs($me);

    $this->getJson('/api/v1/profile/files')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'mine.pdf');
});

it('admins can delete any file', function () {
    [$project] = projectWithTeam();
    $file = Attachment::factory()->forProject($project)->create();
    actingAsRole(UserRole::Admin);

    $this->deleteJson("/api/v1/files/{$file->id}")->assertOk();
});
