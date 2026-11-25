<?php

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

it('adds security headers to API responses', function () {
    $this->getJson('/api/v1/health')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertHeader('Cache-Control', 'no-store, private');
});

it('gives every response a request id and keeps a valid one from the client', function () {
    $generated = $this->getJson('/api/v1/health')->headers->get('X-Request-Id');
    expect($generated)->toBeString()->toHaveLength(36);

    $this->getJson('/api/v1/health', ['X-Request-Id' => 'frontend-req-12345'])
        ->assertHeader('X-Request-Id', 'frontend-req-12345');

    $this->getJson('/api/v1/health', ['X-Request-Id' => "bad\nvalue"])
        ->assertHeader('X-Request-Id');
});

it('allows CORS only from the frontend origin', function () {
    config(['cors.allowed_origins' => ['http://localhost:5173']]);

    $this->options('/api/v1/health', [], [
        'Origin' => 'http://localhost:5173',
        'Access-Control-Request-Method' => 'GET',
    ])->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');

    $response = $this->options('/api/v1/health', [], [
        'Origin' => 'https://evil.example',
        'Access-Control-Request-Method' => 'GET',
    ]);

    expect($response->headers->get('Access-Control-Allow-Origin'))->not->toBe('https://evil.example');
});

it('rate limits uploads per user', function () {
    Storage::fake('public');
    config(['collabspace.rate_limits.uploads' => 2]);
    actingAsRole(UserRole::TeamMember);

    foreach (range(1, 2) as $ignored) {
        $this->post('/api/v1/profile/avatar', ['avatar' => UploadedFile::fake()->image('me.png')])->assertOk();
    }

    $this->post('/api/v1/profile/avatar', ['avatar' => UploadedFile::fake()->image('me.png')])
        ->assertStatus(429)
        ->assertJsonPath('success', false);
});

it('shows the activity log to admins with filters', function () {
    $pm = User::factory()->projectManager()->create();
    $project = Project::factory()->managedBy($pm)->create();
    activity('auth')->causedBy($pm)->performedOn($pm)->event('login')->log('Logged in');

    actingAsRole(UserRole::Admin);

    $this->getJson('/api/v1/admin/activity-log?log=auth&event=login')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.causer.id', $pm->id)
        ->assertJsonPath('data.0.subject.type', 'user');

    $entry = $this->getJson("/api/v1/admin/activity-log?subject_type=project&subject_id={$project->id}")
        ->assertOk()
        ->assertJsonPath('data.0.log', 'projects')
        ->json('data.0.id');

    $this->getJson("/api/v1/admin/activity-log/{$entry}")->assertOk()->assertJsonPath('data.subject.id', $project->id);

    $this->getJson('/api/v1/admin/activity-log?subject_type=salary')->assertUnprocessable();
});

it('hides the activity log from non admins', function () {
    $entry = activity('auth')->log('Logged in');

    Sanctum::actingAs(User::factory()->projectManager()->create());

    $this->getJson('/api/v1/admin/activity-log')->assertForbidden();
    $this->getJson("/api/v1/admin/activity-log/{$entry->id}")->assertForbidden();
});
