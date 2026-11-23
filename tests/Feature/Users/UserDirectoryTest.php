<?php

use App\Enums\UserRole;
use App\Models\User;

it('lists active users as summaries with pagination', function () {
    actingAsRole(UserRole::TeamMember, ['name' => 'Me']);
    User::factory()->create(['name' => 'Sara Hassan']);
    User::factory()->inactive()->create(['name' => 'Hidden Person']);

    $this->getJson('/api/v1/users')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonMissingPath('data.0.email')
        ->assertJsonMissingPath('data.0.phone')
        ->assertJsonPath('meta.pagination.total', 2)
        ->assertJsonMissing(['name' => 'Hidden Person']);
});

it('searches users by name', function () {
    actingAsRole(UserRole::TeamMember, ['name' => 'Me']);
    User::factory()->create(['name' => 'Sara Hassan']);
    User::factory()->create(['name' => 'Ali Mohamed']);

    $this->getJson('/api/v1/users?search=sara')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Sara Hassan');
});

it('does not let regular users filter by role or status', function () {
    actingAsRole(UserRole::TeamMember);
    User::factory()->inactive()->create();

    // Extra keys are dropped by validation, so inactive users stay hidden.
    $this->getJson('/api/v1/users?is_active=0')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('hides deactivated users from the profile endpoint', function () {
    actingAsRole(UserRole::TeamMember);
    $inactive = User::factory()->inactive()->create();

    $this->getJson("/api/v1/users/{$inactive->id}")->assertNotFound();
});

it('requires authentication', function () {
    $this->getJson('/api/v1/users')->assertUnauthorized();
});
