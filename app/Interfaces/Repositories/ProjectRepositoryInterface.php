<?php

namespace App\Interfaces\Repositories;

use App\Filters\QueryFilter;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<Project>
 */
interface ProjectRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Projects list, limited to what the user is allowed to see.
     *
     * @return LengthAwarePaginator<int, Project>
     */
    public function paginateVisibleTo(User $user, QueryFilter $filter, int $perPage = 15): LengthAwarePaginator;

    /**
     * Checks deleted projects too, because the slug column is unique.
     */
    public function slugExists(string $slug): bool;

    /**
     * @param  list<int>  $teamIds
     */
    public function attachTeams(Project $project, array $teamIds): void;

    public function detachTeam(Project $project, Team $team): void;

    /**
     * @param  list<int>  $userIds
     */
    public function attachGuests(Project $project, array $userIds, User $addedBy): void;

    public function detachGuest(Project $project, User $guest): void;

    /**
     * Everyone in the project (manager + team members + guests), active users only.
     *
     * @return Collection<int, User>
     */
    public function members(Project $project): Collection;

    /**
     * Loads everything the project details screen needs.
     */
    public function loadDetails(Project $project): Project;
}
