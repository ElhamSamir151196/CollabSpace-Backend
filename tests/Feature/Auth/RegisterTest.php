<?php

use App\Enums\UserRole;
use App\Models\User;

function validRegistration(array $overrides = []): array
{
    return array_merge([
        'name' => 'Sara Hassan',
        'email' => 'Sara@Collab.test ',
        'password' => 'secret-pass-1',
        'password_confirmation' => 'secret-pass-1',
    ], $overrides);
}

it('registers a team member and returns a token', function () {
    $response = $this->postJson('/api/v1/auth/register', validRegistration());

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.email', 'sara@collab.test')
        ->assertJsonPath('data.user.role.value', UserRole::TeamMember->value)
        ->assertJsonPath('data.permissions', [])
        ->assertJsonPath('data.token.token_type', 'Bearer')
        ->assertJsonStructure(['data' => ['token' => ['access_token', 'expires_at']]]);

    $user = User::query()->where('email', 'sara@collab.test')->firstOrFail();
    expect($user->hasRole(UserRole::TeamMember->value))->toBeTrue()
        ->and($user->tokens()->count())->toBe(1);
});

it('never exposes the password or google id', function () {
    $this->postJson('/api/v1/auth/register', validRegistration())
        ->assertJsonMissingPath('data.user.password')
        ->assertJsonMissingPath('data.user.google_id');
});

it('validates the registration fields', function (array $overrides, string $field) {
    $this->postJson('/api/v1/auth/register', validRegistration($overrides))
        ->assertStatus(422)
        ->assertJsonValidationErrors($field);
})->with([
    'name required' => [['name' => ''], 'name'],
    'email invalid' => [['email' => 'nope'], 'email'],
    'password too short' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'confirmation mismatch' => [['password_confirmation' => 'other-pass-1'], 'password'],
]);

it('rejects an email that is already registered (case-insensitive)', function () {
    User::factory()->create(['email' => 'sara@collab.test']);

    $this->postJson('/api/v1/auth/register', validRegistration(['email' => 'SARA@collab.test']))
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('cannot choose its own role', function () {
    $this->postJson('/api/v1/auth/register', validRegistration(['role' => 'admin']))
        ->assertCreated()
        ->assertJsonPath('data.user.role.value', UserRole::TeamMember->value);
});
