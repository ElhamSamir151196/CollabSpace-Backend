<?php

use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;

it('shows a team member only the projects they belong to', function () {
    $me = actingAsRole(UserRole::TeamMember);
    $team = Team::factory()->create();
    $team->members()->attach($me->id);

    $viaTeam = Project::factory()->withTeams([$team])->create(['name' => 'Via team']);
    $asGuest = Project::factory()->withGuests([$me])->create(['name' => 'As guest']);
    Project::factory()->create(['name' => 'Not mine']);

    $this->getJson('/api/v1/projects')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonMissing(['name' => 'Not mine']);
});

it('shows a project manager the projects they manage', function () {
    $pm = actingAsRole(UserRole::ProjectManager);
    Project::factory()->managedBy($pm)->create(['name' => 'Mine']);
    Project::factory()->create(['name' => 'Other']);

    $this->getJson('/api/v1/projects')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Mine');
});

it('shows an admin every project', function () {
    actingAsRole(UserRole::Admin);
    Project::factory()->count(3)->create();

    $this->getJson('/api/v1/projects')->assertJsonCount(3, 'data');
});

it('filters projects', function (string $query, string $expected) {
    actingAsRole(UserRole::Admin);
    $team = Team::factory()->create();
    $pm = User::factory()->projectManager()->create();

    // Fixed dates/priorities so random factory values can never match another filter.
    Project::factory()->withStatus(ProjectStatus::Completed)->create(['name' => 'Done project', 'priority' => 'low', 'deadline' => '2027-08-01']);
    Project::factory()->overdue()->create(['name' => 'Late project', 'priority' => 'critical']);
    Project::factory()->withTeams([$team])->create(['name' => 'Team project', 'priority' => 'medium', 'deadline' => '2027-03-01', 'start_date' => '2027-01-01']);
    Project::factory()->managedBy($pm)->create(['name' => 'Managed project', 'priority' => 'medium', 'description' => 'Hotel booking', 'deadline' => '2027-06-01']);

    $query = str_replace([':team', ':pm'], [$team->id, $pm->id], $query);

    $this->getJson("/api/v1/projects?{$query}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', $expected);
})->with([
    'status' => ['status=completed', 'Done project'],
    'priority' => ['priority=critical', 'Late project'],
    'overdue' => ['overdue=1', 'Late project'],
    'team' => ['team_id=:team', 'Team project'],
    'manager' => ['manager_id=:pm', 'Managed project'],
    'search in description' => ['search=hotel', 'Managed project'],
    'deadline range' => ['deadline_from=2027-02-01&deadline_to=2027-03-31', 'Team project'],
]);

it('marks overdue projects', function () {
    actingAsRole(UserRole::Admin);
    Project::factory()->overdue()->create();

    $this->getJson('/api/v1/projects')->assertJsonPath('data.0.is_overdue', true);
});

it('sorts projects by deadline', function () {
    actingAsRole(UserRole::Admin);
    Project::factory()->create(['name' => 'Later', 'start_date' => '2026-01-01', 'deadline' => '2026-12-01']);
    Project::factory()->create(['name' => 'Sooner', 'start_date' => '2026-01-01', 'deadline' => '2026-06-01']);

    $this->getJson('/api/v1/projects?sort=deadline')->assertJsonPath('data.0.name', 'Sooner');
    $this->getJson('/api/v1/projects?sort=-deadline')->assertJsonPath('data.0.name', 'Later');
});
