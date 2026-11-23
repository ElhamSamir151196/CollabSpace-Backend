<?php

use App\Enums\UserRole;
use App\Models\User;

it('forbids non-admins from every admin user endpoint', function (UserRole $role) {
    actingAsRole($role);
    $other = User::factory()->create();

    $this->getJson('/api/v1/admin/users')->assertForbidden();
    $this->postJson('/api/v1/admin/users', [])->assertForbidden();
    $this->getJson("/api/v1/admin/users/{$other->id}")->assertForbidden();
    $this->putJson("/api/v1/admin/users/{$other->id}", ['name' => 'x'])->assertForbidden();
    $this->patchJson("/api/v1/admin/users/{$other->id}/role", ['role' => 'admin'])->assertForbidden();
    $this->patchJson("/api/v1/admin/users/{$other->id}/status", ['is_active' => false])->assertForbidden();
    $this->deleteJson("/api/v1/admin/users/{$other->id}")->assertForbidden();
})->with([UserRole::ProjectManager, UserRole::TeamMember]);

it('lists users with role and status filters', function () {
    actingAsRole(UserRole::Admin);
    User::factory()->projectManager()->create(['name' => 'PM One']);
    User::factory()->teamMember()->inactive()->create(['name' => 'Off Member']);

    $this->getJson('/api/v1/admin/users?role=project_manager')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'PM One')
        ->assertJsonPath('data.0.role.value', 'project_manager');

    $this->getJson('/api/v1/admin/users?is_active=0')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Off Member');
});

it('creates a user with a role', function () {
    actingAsRole(UserRole::Admin);

    $this->postJson('/api/v1/admin/users', [
        'name' => 'New PM',
        'email' => 'New.PM@collab.test',
        'password' => 'initial-pass-1',
        'role' => 'project_manager',
        'job_title' => 'Project Manager',
    ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'new.pm@collab.test')
        ->assertJsonPath('data.role.value', 'project_manager');
});

it('updates a user and logs them out when the admin sets a new password', function () {
    actingAsRole(UserRole::Admin);
    $user = User::factory()->create();
    $user->createToken('laptop');

    $this->putJson("/api/v1/admin/users/{$user->id}", ['job_title' => 'QA Lead', 'password' => 'admin-set-pass-1'])
        ->assertOk()
        ->assertJsonPath('data.job_title', 'QA Lead');

    expect($user->tokens()->count())->toBe(0);
});

it('changes a user role', function () {
    actingAsRole(UserRole::Admin);
    $user = User::factory()->teamMember()->create();

    $this->patchJson("/api/v1/admin/users/{$user->id}/role", ['role' => 'project_manager'])
        ->assertOk()
        ->assertJsonPath('data.role.value', 'project_manager');

    expect($user->fresh()->getRoleNames()->all())->toBe(['project_manager']);
    $this->assertDatabaseHas('activity_log', ['event' => 'role_changed', 'subject_id' => $user->id]);
});

it('deactivates a user and revokes their tokens', function () {
    actingAsRole(UserRole::Admin);
    $user = User::factory()->create();
    $user->createToken('laptop');

    $this->patchJson("/api/v1/admin/users/{$user->id}/status", ['is_active' => false])
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    expect($user->tokens()->count())->toBe(0);
});

it('soft deletes a user', function () {
    actingAsRole(UserRole::Admin);
    $user = User::factory()->create();

    $this->deleteJson("/api/v1/admin/users/{$user->id}")->assertOk();

    $this->assertSoftDeleted($user);
    $this->getJson("/api/v1/admin/users/{$user->id}")->assertNotFound();
});

it('stops an admin from locking themselves out', function () {
    $admin = actingAsRole(UserRole::Admin);

    $this->patchJson("/api/v1/admin/users/{$admin->id}/role", ['role' => 'team_member'])
        ->assertStatus(422)->assertJsonPath('message', 'You cannot remove your own admin role.');
    $this->patchJson("/api/v1/admin/users/{$admin->id}/status", ['is_active' => false])
        ->assertStatus(422)->assertJsonPath('message', 'You cannot deactivate your own account.');
    $this->deleteJson("/api/v1/admin/users/{$admin->id}")
        ->assertStatus(422)->assertJsonPath('message', 'You cannot delete your own account.');

    expect($admin->fresh()->hasRole('admin'))->toBeTrue();
});
