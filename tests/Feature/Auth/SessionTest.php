<?php

use App\Enums\UserRole;
use App\Models\User;

it('returns the current user from /auth/me', function () {
    $user = actingAsRole(UserRole::Admin, ['email' => 'admin@collab.test']);

    $this->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.user.role.value', 'admin')
        ->assertJsonMissingPath('data.token');
});

it('requires a token for /auth/me', function () {
    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});

it('logs out only the current device', function () {
    $user = User::factory()->create();
    $current = $user->createToken('laptop')->plainTextToken;
    $user->createToken('phone');

    $this->withToken($current)->postJson('/api/v1/auth/logout')->assertOk();

    expect($user->tokens()->pluck('name')->all())->toBe(['phone']);
});

it('rejects an expired token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('web', ['*'], now()->subMinute())->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
});

it('blocks a user that was deactivated after logging in', function () {
    $user = User::factory()->create();
    $token = $user->createToken('web')->plainTextToken;
    $user->update(['is_active' => false]);

    $this->withToken($token)->getJson('/api/v1/auth/me')->assertForbidden();
});
