<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('sends a reset link that points to the React app', function () {
    Notification::fake();
    config(['collabspace.frontend_url' => 'https://app.collab.test']);
    $user = User::factory()->create(['email' => 'sara@collab.test']);

    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'sara@collab.test'])->assertOk();

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $url = $notification->toMail($user)->actionUrl;

        return str_starts_with($url, 'https://app.collab.test/reset-password?token=')
            && str_contains($url, 'email=sara%40collab.test');
    });
});

it('answers the same way for an unknown email', function () {
    Notification::fake();

    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@collab.test'])
        ->assertOk()
        ->assertJsonPath('message', 'If this email is registered, a reset link has been sent.');

    Notification::assertNothingSent();
});

it('resets the password and logs out every device', function () {
    $user = User::factory()->create(['email' => 'sara@collab.test']);
    $user->createToken('laptop');
    $token = Password::broker()->createToken($user);

    $this->postJson('/api/v1/auth/reset-password', [
        'email' => 'sara@collab.test',
        'token' => $token,
        'password' => 'brand-new-pass-1',
        'password_confirmation' => 'brand-new-pass-1',
    ])->assertOk();

    $user->refresh();
    expect(Hash::check('brand-new-pass-1', $user->password))->toBeTrue()
        ->and($user->tokens()->count())->toBe(0);
});

it('rejects an invalid reset token', function () {
    User::factory()->create(['email' => 'sara@collab.test']);

    $this->postJson('/api/v1/auth/reset-password', [
        'email' => 'sara@collab.test',
        'token' => 'not-a-real-token',
        'password' => 'brand-new-pass-1',
        'password_confirmation' => 'brand-new-pass-1',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});
