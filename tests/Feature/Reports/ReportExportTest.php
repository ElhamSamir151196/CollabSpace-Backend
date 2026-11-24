<?php

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\ReportExport;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake('local');
    $this->travelTo(now()->parse('2025-12-15 12:00'));
});

/**
 * Follows a signed download link and returns the response.
 */
function downloadExport(string $url): TestResponse
{
    // Drop the signed-in user: the link must work without a token.
    app('auth')->forgetGuards();

    return test()->get($url);
}

it('generates every report type as a real PDF', function (string $type) {
    $admin = actingAsRole(UserRole::Admin);
    [$project, $team, [$member]] = projectWithTeam();
    Task::factory()->for($project)->forTeam($team)->assignedTo([$member])->withStatus(TaskStatus::Completed)->create();
    Task::factory()->for($project)->forTeam($team)->assignedTo([$member])->create();

    $response = $this->postJson('/api/v1/reports/exports', [
        'type' => $type,
        'note' => 'Monthly review',
        'from' => '2025-07-01',
        'to' => '2025-12-31',
        'user_id' => $type === 'user' ? $member->id : null,
    ])->assertCreated()
        ->assertJsonPath('data.type.value', $type)
        ->assertJsonPath('data.period', ['from' => '2025-07-01', 'to' => '2025-12-31']);

    $export = ReportExport::query()->firstOrFail();
    $bytes = Storage::disk('local')->get($export->path);

    expect($bytes)->toStartWith('%PDF')
        ->and($export->size)->toBe(strlen($bytes))
        ->and($export->user_id)->toBe($admin->id)
        ->and($response->json('data.download_url'))->toContain('signature=');
})->with(['overview', 'project', 'task', 'team', 'user']);

it('prints an Arabic note without breaking the PDF', function () {
    actingAsRole(UserRole::Admin);
    projectWithTeam();

    $this->postJson('/api/v1/reports/exports', [
        'type' => 'project',
        'note' => 'ملاحظة: يجب مراجعة المشاريع المتأخرة قبل نهاية الشهر',
    ])->assertCreated()->assertJsonPath('data.note', 'ملاحظة: يجب مراجعة المشاريع المتأخرة قبل نهاية الشهر');

    expect(Storage::disk('local')->get(ReportExport::query()->firstOrFail()->path))->toStartWith('%PDF');
});

it('downloads the PDF through the signed link', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$project] = projectWithTeam($pm);

    $url = $this->postJson('/api/v1/reports/exports', ['type' => 'project', 'project_id' => $project->id])
        ->assertCreated()
        ->json('data.download_url');

    $response = downloadExport($url)->assertOk();

    expect($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->headers->get('Content-Disposition'))->toContain("project-report-{$project->slug}-2025-12-15.pdf")
        ->and($response->streamedContent())->toStartWith('%PDF');

    downloadExport($url.'x')->assertForbidden();
});

it('applies the same access rules as the reports', function () {
    $pm = User::factory()->projectManager()->create();
    [$project, , [$member, $colleague]] = projectWithTeam($pm);
    $otherProject = Project::factory()->create();

    // A member: own user report only.
    Sanctum::actingAs($member);
    $this->postJson('/api/v1/reports/exports', ['type' => 'user'])->assertCreated();
    $this->postJson('/api/v1/reports/exports', ['type' => 'user', 'user_id' => $colleague->id])->assertForbidden();
    $this->postJson('/api/v1/reports/exports', ['type' => 'project'])->assertForbidden();

    // A project manager: only the projects they manage.
    Sanctum::actingAs($pm);
    $this->postJson('/api/v1/reports/exports', ['type' => 'team', 'project_id' => $project->id])->assertCreated();
    $this->postJson('/api/v1/reports/exports', ['type' => 'team', 'project_id' => $otherProject->id])->assertForbidden();
    $this->postJson('/api/v1/reports/exports', ['type' => 'user', 'user_id' => $member->id])->assertCreated();
});

it('validates the form', function () {
    actingAsRole(UserRole::Admin);

    $this->postJson('/api/v1/reports/exports', ['type' => 'weekly'])->assertStatus(422)->assertJsonValidationErrors('type');
    $this->postJson('/api/v1/reports/exports', ['type' => 'user', 'user_id' => 999])->assertStatus(422)->assertJsonValidationErrors('user_id');
    $this->postJson('/api/v1/reports/exports', ['type' => 'task', 'from' => '2025-12-10', 'to' => '2025-12-01'])->assertStatus(422);
});

it('keeps a private history and deletes the file with the record', function () {
    $me = actingAsRole(UserRole::Admin);
    $this->postJson('/api/v1/reports/exports', ['type' => 'overview'])->assertCreated();
    $this->postJson('/api/v1/reports/exports', ['type' => 'task'])->assertCreated();

    $this->getJson('/api/v1/reports/exports')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.type.value', 'task');

    $mine = ReportExport::query()->where('type', 'overview')->firstOrFail();

    actingAsRole(UserRole::Admin);
    $this->getJson('/api/v1/reports/exports')->assertJsonCount(0, 'data');
    $this->deleteJson("/api/v1/reports/exports/{$mine->id}")->assertForbidden();

    Sanctum::actingAs($me);
    $this->deleteJson("/api/v1/reports/exports/{$mine->id}")->assertOk();
    Storage::disk('local')->assertMissing($mine->path);
    $this->assertDatabaseMissing('report_exports', ['id' => $mine->id]);
});
