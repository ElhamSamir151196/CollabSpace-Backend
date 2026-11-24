<?php

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests run on a fresh in-memory SQLite database with the
| roles & permissions already seeded.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class))
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Creates a user with the given role and authenticates the next requests as them.
 *
 * @param  array<string, mixed>  $attributes
 */
/**
 * A project managed by $manager (a new PM when null) with one team of $members users.
 *
 * @return array{0: Project, 1: Team, 2: list<User>}
 */
function projectWithTeam(?User $manager = null, int $members = 2): array
{
    $manager ??= User::factory()->projectManager()->create();
    $team = Team::factory()->create();
    $users = User::factory()->count($members)->create()->all();
    $team->members()->attach(array_map(fn (User $user) => $user->id, $users));

    $project = Project::factory()->managedBy($manager)->withTeams([$team])->create();

    return [$project, $team, $users];
}

/**
 * The notification kinds a user has received, oldest first.
 *
 * @return list<string>
 */
function notificationKinds(User $user): array
{
    return $user->notifications()->reorder()->orderBy('created_at')->pluck('type')->all();
}

function actingAsRole(UserRole $role, array $attributes = []): User
{
    $user = User::factory()->withRole($role)->create($attributes);

    Sanctum::actingAs($user);

    return $user;
}
