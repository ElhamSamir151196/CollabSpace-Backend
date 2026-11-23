<?php

use App\Enums\UserRole;
use App\Models\Team;
use App\Models\User;

function leaderIds(Team $team): array
{
    return $team->leaders()->pluck('users.id')->all();
}

it('lists the members of a team', function () {
    actingAsRole(UserRole::TeamMember);
    $team = Team::factory()->withMembers(2)->create();

    $this->getJson("/api/v1/teams/{$team->id}/members")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.is_leader', true);
});

it('lets a project manager add members', function () {
    actingAsRole(UserRole::ProjectManager);
    $team = Team::factory()->create();
    $user = User::factory()->create();

    $this->postJson("/api/v1/teams/{$team->id}/members", [
        'members' => [['user_id' => $user->id, 'position' => 'Backend Dev']],
    ])
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.position', 'Backend Dev')
        ->assertJsonPath('data.0.is_leader', false);
});

it('updates existing members instead of duplicating them', function () {
    actingAsRole(UserRole::Admin);
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->members()->attach($user->id, ['position' => 'Junior']);

    $this->postJson("/api/v1/teams/{$team->id}/members", [
        'members' => [['user_id' => $user->id, 'position' => 'Senior']],
    ])->assertOk();

    expect($team->members()->count())->toBe(1)
        ->and($team->members()->first()->membership->position)->toBe('Senior');
});

it('keeps only one leader when a new leader is added', function () {
    actingAsRole(UserRole::Admin);
    $team = Team::factory()->withMembers(2)->create();
    $newLeader = User::factory()->create();

    $this->postJson("/api/v1/teams/{$team->id}/members", [
        'members' => [['user_id' => $newLeader->id, 'is_leader' => true]],
    ])->assertOk();

    expect(leaderIds($team))->toBe([$newLeader->id]);
});

it('makes a member the leader and removes the old leader', function () {
    actingAsRole(UserRole::Admin);
    $team = Team::factory()->withMembers(3)->create();
    $member = $team->members()->wherePivot('is_leader', false)->first();

    $this->patchJson("/api/v1/teams/{$team->id}/members/{$member->id}", ['is_leader' => true, 'position' => 'Back Leader'])
        ->assertOk()
        ->assertJsonPath('data.id', $member->id)
        ->assertJsonPath('data.is_leader', true)
        ->assertJsonPath('data.position', 'Back Leader');

    expect(leaderIds($team))->toBe([$member->id]);
});

it('removes a member from the team', function () {
    actingAsRole(UserRole::ProjectManager);
    $team = Team::factory()->withMembers(2)->create();
    $member = $team->members()->first();

    $this->deleteJson("/api/v1/teams/{$team->id}/members/{$member->id}")->assertOk();

    expect($team->members()->count())->toBe(1);
    $this->assertDatabaseHas('users', ['id' => $member->id]);
});

it('returns 404 for a user who is not in the team', function () {
    actingAsRole(UserRole::Admin);
    $team = Team::factory()->withMembers(1)->create();
    $outsider = User::factory()->create();

    $this->patchJson("/api/v1/teams/{$team->id}/members/{$outsider->id}", ['position' => 'x'])->assertNotFound();
    $this->deleteJson("/api/v1/teams/{$team->id}/members/{$outsider->id}")->assertNotFound();
});

it('does not let team members manage members', function () {
    actingAsRole(UserRole::TeamMember);
    $team = Team::factory()->withMembers(1)->create();
    $member = $team->members()->first();
    $user = User::factory()->create();

    $this->postJson("/api/v1/teams/{$team->id}/members", ['members' => [['user_id' => $user->id]]])->assertForbidden();
    $this->patchJson("/api/v1/teams/{$team->id}/members/{$member->id}", ['position' => 'x'])->assertForbidden();
    $this->deleteJson("/api/v1/teams/{$team->id}/members/{$member->id}")->assertForbidden();
});
