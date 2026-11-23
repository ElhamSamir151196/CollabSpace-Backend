<?php

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;

it('lists the teams of a project with their members', function () {
    actingAsRole(UserRole::Admin);
    $team = Team::factory()->withMembers(2)->create();
    $project = Project::factory()->withTeams([$team])->create();

    $this->getJson("/api/v1/projects/{$project->slug}/teams")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonCount(2, 'data.0.members')
        ->assertJsonPath('data.0.members_count', 2);
});

it('lets the manager assign teams without duplicating them', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$frontend, $backend] = Team::factory()->count(2)->create()->all();
    $project = Project::factory()->managedBy($pm)->withTeams([$frontend])->create();

    $this->postJson("/api/v1/projects/{$project->slug}/teams", ['team_ids' => [$frontend->id, $backend->id]])
        ->assertOk()
        ->assertJsonCount(2, 'data');

    expect($project->teams()->count())->toBe(2);
});

it('unassigns a team, and returns 404 for a team that is not assigned', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    [$assigned, $other] = Team::factory()->count(2)->create()->all();
    $project = Project::factory()->managedBy($pm)->withTeams([$assigned])->create();

    $this->deleteJson("/api/v1/projects/{$project->slug}/teams/{$other->id}")->assertNotFound();
    $this->deleteJson("/api/v1/projects/{$project->slug}/teams/{$assigned->id}")->assertOk();

    expect($project->teams()->count())->toBe(0);
});

it('adds and removes guests', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    $project = Project::factory()->managedBy($pm)->create();
    $guest = User::factory()->create();

    $this->postJson("/api/v1/projects/{$project->slug}/guests", ['guest_ids' => [$guest->id]])
        ->assertOk()
        ->assertJsonPath('data.0.id', $guest->id);

    $this->assertDatabaseHas('project_user', ['project_id' => $project->id, 'user_id' => $guest->id, 'added_by' => $pm->id]);

    $this->deleteJson("/api/v1/projects/{$project->slug}/guests/{$guest->id}")->assertOk();
    expect($project->guests()->count())->toBe(0);
});

it('does not let members change the teams or guests of a project', function () {
    $me = actingAsRole(UserRole::TeamMember);
    $team = Team::factory()->create();
    $project = Project::factory()->withGuests([$me])->create();

    $this->postJson("/api/v1/projects/{$project->slug}/teams", ['team_ids' => [$team->id]])->assertForbidden();
    $this->postJson("/api/v1/projects/{$project->slug}/guests", ['guest_ids' => [$me->id]])->assertForbidden();
    $this->deleteJson("/api/v1/projects/{$project->slug}/guests/{$me->id}")->assertForbidden();
});

it('lists every project member once: manager, team members and guests', function () {
    actingAsRole(UserRole::Admin);
    $pm = User::factory()->projectManager()->create(['name' => 'A Manager']);
    $team = Team::factory()->create();
    [$inTeam, $alsoGuest] = User::factory()->count(2)->create()->all();
    $team->members()->attach([$inTeam->id, $alsoGuest->id]);
    $guestOnly = User::factory()->create();
    $inactive = User::factory()->inactive()->create();

    $project = Project::factory()
        ->managedBy($pm)
        ->withTeams([$team])
        ->withGuests([$alsoGuest, $guestOnly, $inactive])
        ->create();

    $ids = collect($this->getJson("/api/v1/projects/{$project->slug}/members")->assertOk()->json('data'))->pluck('id');

    expect($ids->sort()->values()->all())
        ->toBe(collect([$pm->id, $inTeam->id, $alsoGuest->id, $guestOnly->id])->sort()->values()->all());
});
