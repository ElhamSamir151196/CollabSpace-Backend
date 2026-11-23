<?php

use App\Enums\UserRole;
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
function actingAsRole(UserRole $role, array $attributes = []): User
{
    $user = User::factory()->withRole($role)->create($attributes);

    Sanctum::actingAs($user);

    return $user;
}
