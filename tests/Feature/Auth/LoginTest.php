<?php

use App\Enums\Permission;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

it('logs in with valid credentials and returns permissions', function () {
    User::factory()->projectManager()->create(['email' => 'pm@collab.test']);

    $this->postJson('/api/v1/auth/login', ['email' => 'PM@collab.test', 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('data.user.email', 'pm@collab.test')
        ->assertJsonPath('data.user.role.value', 'project_manager')
        ->assertJsonFragment([Permission::ProjectsCreate->value])
        ->assertJsonStructure(['data' => ['token' => ['access_token', 'token_type', 'expires_at']]]);
});

it('gives a longer token lifetime with remember me', function (bool $remember, int $expectedHours) {
    // Token dates are stored to the second, so freeze on a whole second.
    $this->freezeSecond();
    User::factory()->create(['email' => 'sara@collab.test']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'sara@collab.test',
        'password' => 'password',
        'remember' => $remember,
    ])->assertOk();

    $token = PersonalAccessToken::query()->firstOrFail();
    expect((int) now()->diffInHours($token->expires_at))->toBe($expectedHours);
})->with([
    'normal' => [false, 24],
    'remember me' => [true, 24 * 30],
]);

it('returns the same error for a wrong password and an unknown email', function (string $email, string $password) {
    User::factory()->create(['email' => 'sara@collab.test']);

    $this->postJson('/api/v1/auth/login', ['email' => $email, 'password' => $password])
        ->assertUnauthorized()
        ->assertJsonPath('message', 'These credentials do not match our records.');
})->with([
    'wrong password' => ['sara@collab.test', 'wrong-password'],
    'unknown email' => ['nobody@collab.test', 'password'],
]);

it('rejects password login for a Google-only account', function () {
    User::factory()->googleOnly()->create(['email' => 'g@collab.test']);

    $this->postJson('/api/v1/auth/login', ['email' => 'g@collab.test', 'password' => ''])
        ->assertStatus(422);

    $this->postJson('/api/v1/auth/login', ['email' => 'g@collab.test', 'password' => 'anything'])
        ->assertUnauthorized();
});

it('blocks deactivated users', function () {
    User::factory()->inactive()->create(['email' => 'off@collab.test']);

    $this->postJson('/api/v1/auth/login', ['email' => 'off@collab.test', 'password' => 'password'])
        ->assertForbidden()
        ->assertJsonPath('message', 'Your account has been deactivated.');
});

it('rate limits login attempts', function () {
    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/v1/auth/login', ['email' => 'x@collab.test', 'password' => 'bad'])->assertUnauthorized();
    }

    $this->postJson('/api/v1/auth/login', ['email' => 'x@collab.test', 'password' => 'bad'])
        ->assertStatus(429)
        ->assertJsonPath('success', false);
});

it('logs failed and successful logins to the activity log', function () {
    User::factory()->create(['email' => 'sara@collab.test']);

    $this->postJson('/api/v1/auth/login', ['email' => 'sara@collab.test', 'password' => 'bad']);
    $this->postJson('/api/v1/auth/login', ['email' => 'sara@collab.test', 'password' => 'password']);

    $this->assertDatabaseHas('activity_log', ['log_name' => 'auth', 'event' => 'login_failed']);
    $this->assertDatabaseHas('activity_log', ['log_name' => 'auth', 'event' => 'login']);
});
