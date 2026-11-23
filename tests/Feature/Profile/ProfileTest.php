<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

it('shows the signed-in user profile', function () {
    $user = actingAsRole(UserRole::TeamMember);

    $this->getJson('/api/v1/profile')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.role.label', 'Team Member');
});

it('updates only the fields that were sent', function () {
    $user = actingAsRole(UserRole::TeamMember, ['job_title' => 'Old title', 'phone' => '+20 100 000 0000']);

    $this->putJson('/api/v1/profile', [
        'job_title' => 'Senior UI/UX Designer',
        'years_of_experience' => 6,
        'availability_status' => 'busy',
    ])
        ->assertOk()
        ->assertJsonPath('data.job_title', 'Senior UI/UX Designer')
        ->assertJsonPath('data.availability_status', ['value' => 'busy', 'label' => 'Busy'])
        ->assertJsonPath('data.phone', '+20 100 000 0000');
});

it('ignores fields a user must not change on their own profile', function () {
    $user = actingAsRole(UserRole::TeamMember);

    $this->putJson('/api/v1/profile', ['is_active' => false, 'role' => 'admin', 'name' => 'New Name'])->assertOk();

    $user->refresh();
    expect($user->is_active)->toBeTrue()
        ->and($user->hasRole('admin'))->toBeFalse()
        ->and($user->name)->toBe('New Name');
});

it('rejects an email used by someone else', function () {
    User::factory()->create(['email' => 'taken@collab.test']);
    actingAsRole(UserRole::TeamMember);

    $this->putJson('/api/v1/profile', ['email' => 'taken@collab.test'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('uploads a new avatar and deletes the old one', function () {
    Storage::fake('public');
    $user = actingAsRole(UserRole::TeamMember);

    $this->post('/api/v1/profile/avatar', ['avatar' => UploadedFile::fake()->image('one.jpg')])->assertOk();
    $firstPath = $user->fresh()->avatar_path;

    $this->post('/api/v1/profile/avatar', ['avatar' => UploadedFile::fake()->image('two.png')])
        ->assertOk()
        ->assertJsonPath('data.avatar_url', fn (string $url) => str_contains($url, 'avatars/'));

    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($user->fresh()->avatar_path);
});

it('rejects non-image avatars', function () {
    Storage::fake('public');
    actingAsRole(UserRole::TeamMember);

    $this->post('/api/v1/profile/avatar', ['avatar' => UploadedFile::fake()->create('virus.php', 10, 'text/x-php')])
        ->assertStatus(422)
        ->assertJsonValidationErrors('avatar');
});

it('removes the avatar', function () {
    Storage::fake('public');
    $user = actingAsRole(UserRole::TeamMember);
    $this->post('/api/v1/profile/avatar', ['avatar' => UploadedFile::fake()->image('one.jpg')]);
    $path = $user->fresh()->avatar_path;

    $this->deleteJson('/api/v1/profile/avatar')->assertOk()->assertJsonPath('data.avatar_url', null);

    Storage::disk('public')->assertMissing($path);
});

it('changes the password and logs out other devices only', function () {
    $user = User::factory()->create();
    $current = $user->createToken('laptop')->plainTextToken;
    $user->createToken('phone');

    $this->withToken($current)->putJson('/api/v1/profile/password', [
        'current_password' => 'password',
        'password' => 'new-secret-pass-1',
        'password_confirmation' => 'new-secret-pass-1',
    ])->assertOk();

    expect(Hash::check('new-secret-pass-1', $user->fresh()->password))->toBeTrue()
        ->and($user->tokens()->pluck('name')->all())->toBe(['laptop']);
});

it('requires the correct current password', function () {
    actingAsRole(UserRole::TeamMember);

    $this->putJson('/api/v1/profile/password', [
        'current_password' => 'wrong',
        'password' => 'new-secret-pass-1',
        'password_confirmation' => 'new-secret-pass-1',
    ])->assertStatus(422)->assertJsonValidationErrors('current_password');
});

it('lets a Google-only account set its first password without a current one', function () {
    $user = User::factory()->googleOnly()->create();
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/profile/password', [
        'password' => 'first-pass-123',
        'password_confirmation' => 'first-pass-123',
    ])->assertOk();

    expect($user->fresh()->hasPassword())->toBeTrue();
});
