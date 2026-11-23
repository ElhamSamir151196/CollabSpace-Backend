<?php

use App\Enums\UserRole;
use App\Models\Attachment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(fn () => Storage::fake('local'));

it('lets a project member upload files to the project', function () {
    [$project, $team, [$member]] = projectWithTeam();
    Sanctum::actingAs($member);

    $response = $this->post("/api/v1/projects/{$project->slug}/files", [
        'files' => [
            UploadedFile::fake()->image('mockup.png'),
            UploadedFile::fake()->create('UX_Research_Summary.pdf', 300, 'application/pdf'),
        ],
    ]);

    $response->assertCreated()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'mockup.png')
        ->assertJsonPath('data.0.type.value', 'image')
        ->assertJsonPath('data.0.source.type', 'project')
        ->assertJsonPath('data.0.uploaded_by.id', $member->id)
        ->assertJsonPath('data.1.size_human', '300 KB');

    $attachment = Attachment::query()->where('original_name', 'mockup.png')->firstOrFail();
    Storage::disk('local')->assertExists($attachment->path);

    expect($attachment->path)->toStartWith("projects/{$project->id}/")
        ->and($attachment->path)->not->toContain('mockup')
        ->and($response->json('data.0.preview_url'))->toContain('inline=1')
        ->and($response->json('data.1.preview_url'))->not->toBeNull();
});

it('only gives a preview link for images and PDFs', function () {
    [$project, $team, [$member]] = projectWithTeam();
    Sanctum::actingAs($member);

    $this->post("/api/v1/projects/{$project->slug}/files", [
        'files' => [UploadedFile::fake()->create('Database_Schema_v2.sql', 5, 'text/plain')],
    ])
        ->assertCreated()
        ->assertJsonPath('data.0.type.value', 'code')
        ->assertJsonPath('data.0.preview_url', null);
});

it('validates uploads', function (string $case) {
    [$project, $team, [$member]] = projectWithTeam();
    Sanctum::actingAs($member);
    config(['collabspace.files.max_kb' => 100, 'collabspace.files.max_per_upload' => 2]);

    $files = match ($case) {
        'no files' => [],
        'executable' => [UploadedFile::fake()->create('setup.exe', 10)],
        'php script' => [UploadedFile::fake()->create('shell.php', 1, 'text/x-php')],
        'html page' => [UploadedFile::fake()->create('page.html', 1, 'text/html')],
        'too big' => [UploadedFile::fake()->create('big.pdf', 101, 'application/pdf')],
        'too many' => array_map(fn ($i) => UploadedFile::fake()->create("f{$i}.pdf", 1), [1, 2, 3]),
    };

    $this->post("/api/v1/projects/{$project->slug}/files", ['files' => $files], ['Accept' => 'application/json'])
        ->assertStatus(422);

    expect(Attachment::query()->count())->toBe(0);
})->with(['no files', 'executable', 'php script', 'html page', 'too big', 'too many']);

it('does not let outsiders upload or list project files', function () {
    [$project] = projectWithTeam();
    Sanctum::actingAs(User::factory()->create());

    $this->post("/api/v1/projects/{$project->slug}/files", ['files' => [UploadedFile::fake()->image('x.png')]])
        ->assertForbidden();
    $this->getJson("/api/v1/projects/{$project->slug}/files")->assertForbidden();

    Storage::disk('local')->assertDirectoryEmpty('/');
});

it('uploads files to a task and counts them on the card', function () {
    [$project, $team, [$member]] = projectWithTeam();
    $task = Task::factory()->for($project)->forTeam($team)->create();
    Sanctum::actingAs($member);

    $this->post("/api/v1/tasks/{$task->id}/files", ['files' => [UploadedFile::fake()->image('screen.jpg')]])
        ->assertCreated()
        ->assertJsonPath('data.0.source', ['type' => 'task', 'id' => $task->id, 'title' => $task->title]);

    $this->getJson("/api/v1/tasks/{$task->id}/files")->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/tasks/{$task->id}")->assertJsonPath('data.attachments_count', 1);
});

it('creates a project together with its files (multipart form)', function () {
    actingAsRole(UserRole::ProjectManager);

    $this->post('/api/v1/projects', [
        'name' => 'Alpha',
        'priority' => 'high',
        'start_date' => '2026-12-01',
        'deadline' => '2027-01-31',
        'files' => [UploadedFile::fake()->create('brief.pdf', 20, 'application/pdf')],
    ], ['Accept' => 'application/json'])->assertCreated();

    $this->getJson('/api/v1/projects/alpha/files')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'brief.pdf');
});

it('creates a task together with its files (multipart form)', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project, $team] = projectWithTeam($pm);

    $this->post("/api/v1/projects/{$project->slug}/tasks", [
        'title' => 'Create Wireframes',
        'team_id' => $team->id,
        'files' => [UploadedFile::fake()->image('wireframe.png')],
    ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.attachments_count', 1);
});
