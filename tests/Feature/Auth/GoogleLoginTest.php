<?php

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;

/**
 * Fakes Google: the given access token resolves to this Google profile.
 */
function fakeGoogleUser(string $id, string $email, string $name = 'Google Person'): void
{
    $googleUser = (new SocialiteUser)->map(['id' => $id, 'email' => $email, 'name' => $name]);

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('stateless')->andReturnSelf();
    $provider->shouldReceive('userFromToken')->with('google-token')->andReturn($googleUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
}

it('creates a new team member from a Google account', function () {
    fakeGoogleUser('g-123', 'New.Person@Gmail.com', 'New Person');

    $this->postJson('/api/v1/auth/google', ['access_token' => 'google-token'])
        ->assertOk()
        ->assertJsonPath('data.user.email', 'new.person@gmail.com')
        ->assertJsonPath('data.user.has_password', false)
        ->assertJsonPath('data.user.role.value', UserRole::TeamMember->value);

    $user = User::query()->where('email', 'new.person@gmail.com')->firstOrFail();
    expect($user->google_id)->toBe('g-123')
        ->and($user->email_verified_at)->not->toBeNull();
});

it('links Google to an existing account with the same email', function () {
    $existing = User::factory()->projectManager()->create(['email' => 'sara@gmail.com']);
    fakeGoogleUser('g-456', 'sara@gmail.com');

    $this->postJson('/api/v1/auth/google', ['access_token' => 'google-token'])
        ->assertOk()
        ->assertJsonPath('data.user.id', $existing->id)
        ->assertJsonPath('data.user.role.value', UserRole::ProjectManager->value);

    expect($existing->fresh()->google_id)->toBe('g-456')
        ->and(User::query()->count())->toBe(1);
});

it('rejects an invalid Google token', function () {
    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('stateless')->andReturnSelf();
    $provider->shouldReceive('userFromToken')->andThrow(new RuntimeException('401 from Google'));
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->postJson('/api/v1/auth/google', ['access_token' => 'bad'])
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Invalid or expired Google token.');
});

it('blocks a deactivated account even through Google', function () {
    User::factory()->inactive()->create(['email' => 'off@gmail.com', 'google_id' => 'g-789']);
    fakeGoogleUser('g-789', 'off@gmail.com');

    $this->postJson('/api/v1/auth/google', ['access_token' => 'google-token'])->assertForbidden();
});
