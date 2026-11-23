<?php

use App\Enums\UserRole;
use App\Models\Team;
use App\Models\User;

it('lists teams with member count and leader for any signed-in user', function () {
    actingAsRole(UserRole::TeamMember);
    $team = Team::factory()->withMembers(3)->create(['name' => 'Backend']);
    Team::factory()->create(['name' => 'Alpha Empty']);

    $this->getJson('/api/v1/teams')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Alpha Empty')
        ->assertJsonPath('data.0.members_count', 0)
        ->assertJsonPath('data.0.leader', null)
        ->assertJsonPath('data.1.members_count', 3)
        ->assertJsonPath('data.1.leader.is_leader', true)
        ->assertJsonMissingPath('data.1.members');
});

it('filters teams by search, by member and by "mine"', function () {
    $me = actingAsRole(UserRole::TeamMember);
    $mine = Team::factory()->create(['name' => 'Frontend']);
    $mine->members()->attach($me->id);
    Team::factory()->create(['name' => 'Marketing']);

    $this->getJson('/api/v1/teams?search=market')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Marketing');
    $this->getJson('/api/v1/teams?mine=1')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Frontend');
    $this->getJson("/api/v1/teams?member_id={$me->id}")->assertJsonCount(1, 'data');
});

it('sorts teams by members count', function () {
    actingAsRole(UserRole::TeamMember);
    Team::factory()->withMembers(1)->create(['name' => 'Small']);
    Team::factory()->withMembers(4)->create(['name' => 'Big']);

    $this->getJson('/api/v1/teams?sort=-members_count')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Big');
});

it('shows a team with its members, leader first', function () {
    actingAsRole(UserRole::TeamMember);
    $team = Team::factory()->withMembers(3)->create();

    $this->getJson("/api/v1/teams/{$team->id}")
        ->assertOk()
        ->assertJsonCount(3, 'data.members')
        ->assertJsonPath('data.members.0.is_leader', true)
        ->assertJsonPath('data.leader.id', $team->leaders()->first()->id)
        ->assertJsonStructure(['data' => ['members' => [['id', 'name', 'avatar_url', 'position', 'is_leader', 'joined_at']]]]);
});

it('lets an admin create a team with members and one leader', function () {
    $admin = actingAsRole(UserRole::Admin);
    [$sara, $ali] = User::factory()->count(2)->create()->all();

    $this->postJson('/api/v1/teams', [
        'name' => 'UI/UX',
        'description' => 'Design team',
        'color' => '#f3e8ff',
        'members' => [
            ['user_id' => $sara->id, 'position' => 'UI Designer', 'is_leader' => true],
            ['user_id' => $ali->id, 'position' => 'UX Researcher'],
        ],
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'UI/UX')
        ->assertJsonPath('data.color', '#F3E8FF')
        ->assertJsonPath('data.members_count', 2)
        ->assertJsonPath('data.leader.id', $sara->id)
        ->assertJsonPath('data.created_by.id', $admin->id);
});

it('validates team creation', function (array $payload, string $field) {
    actingAsRole(UserRole::Admin);
    Team::factory()->create(['name' => 'Taken']);
    [$a, $b] = User::factory()->count(2)->create()->all();
    $inactive = User::factory()->inactive()->create();

    $payload = json_decode(str_replace(
        ['":a"', '":b"', '":inactive"'],
        [$a->id, $b->id, $inactive->id],
        json_encode($payload),
    ), true);

    $this->postJson('/api/v1/teams', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors($field);
})->with([
    'name required' => [['name' => ''], 'name'],
    'name unique' => [['name' => 'Taken'], 'name'],
    'bad color' => [['name' => 'X', 'color' => 'purple'], 'color'],
    'duplicate member' => [['name' => 'X', 'members' => [['user_id' => ':a'], ['user_id' => ':a']]], 'members.0.user_id'],
    'inactive member' => [['name' => 'X', 'members' => [['user_id' => ':inactive']]], 'members.0.user_id'],
    'two leaders' => [['name' => 'X', 'members' => [['user_id' => ':a', 'is_leader' => true], ['user_id' => ':b', 'is_leader' => true]]], 'members'],
]);

it('allows reusing the name of a deleted team', function () {
    actingAsRole(UserRole::Admin);
    Team::factory()->create(['name' => 'QA'])->delete();

    $this->postJson('/api/v1/teams', ['name' => 'QA'])->assertCreated();
});

it('lets an admin update and soft delete a team', function () {
    actingAsRole(UserRole::Admin);
    $team = Team::factory()->create(['name' => 'Old']);

    $this->putJson("/api/v1/teams/{$team->id}", ['name' => 'DevOps'])
        ->assertOk()
        ->assertJsonPath('data.name', 'DevOps');

    $this->deleteJson("/api/v1/teams/{$team->id}")->assertOk();

    $this->assertSoftDeleted($team);
    $this->getJson("/api/v1/teams/{$team->id}")->assertNotFound();
});

it('only lets admins create, update or delete teams', function (UserRole $role) {
    actingAsRole($role);
    $team = Team::factory()->create();

    $this->postJson('/api/v1/teams', ['name' => 'New'])->assertForbidden();
    $this->putJson("/api/v1/teams/{$team->id}", ['name' => 'New'])->assertForbidden();
    $this->deleteJson("/api/v1/teams/{$team->id}")->assertForbidden();
})->with([UserRole::ProjectManager, UserRole::TeamMember]);
