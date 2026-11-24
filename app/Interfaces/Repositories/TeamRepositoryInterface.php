<?php

namespace App\Interfaces\Repositories;

use App\DTOs\Team\TeamMemberData;
use App\Filters\QueryFilter;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepositoryInterface<Team>
 */
interface TeamRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Teams list: with members_count and the leader.
     *
     * @return LengthAwarePaginator<int, Team>
     */
    public function paginateForList(QueryFilter $filter, int $perPage = 15): LengthAwarePaginator;

    /**
     * Adds new members or updates the position/leader flag of existing ones.
     *
     * @param  list<TeamMemberData>  $members
     * @return list<int> ids of the users that were not in the team before
     */
    public function upsertMembers(Team $team, array $members): array;

    /**
     * @param  array<string, mixed>  $attributes  position / is_leader
     */
    public function updateMember(Team $team, User $member, array $attributes): void;

    public function removeMember(Team $team, User $member): void;

    /**
     * Removes the leader flag from everyone in the team except $keepUserId.
     */
    public function clearLeaders(Team $team, ?int $keepUserId = null): void;

    /**
     * Loads everything the team details screen needs.
     */
    public function loadDetails(Team $team): Team;
}
