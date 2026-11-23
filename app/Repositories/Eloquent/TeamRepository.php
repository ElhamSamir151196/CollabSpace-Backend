<?php

namespace App\Repositories\Eloquent;

use App\Filters\QueryFilter;
use App\Interfaces\Repositories\TeamRepositoryInterface;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepository<Team>
 */
class TeamRepository extends BaseRepository implements TeamRepositoryInterface
{
    public function __construct(Team $model)
    {
        parent::__construct($model);
    }

    public function paginateForList(QueryFilter $filter, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query()->with('leaders')->withCount('members');

        $filter->apply($query);

        return $query->paginate(max(1, min($perPage, static::MAX_PER_PAGE)));
    }

    public function upsertMembers(Team $team, array $members): void
    {
        $rows = [];

        foreach ($members as $member) {
            $rows[$member->userId] = [
                'position' => $member->position,
                'is_leader' => $member->isLeader,
            ];
        }

        $team->members()->syncWithoutDetaching($rows);
    }

    public function updateMember(Team $team, User $member, array $attributes): void
    {
        $team->members()->updateExistingPivot($member->id, $attributes);
    }

    public function removeMember(Team $team, User $member): void
    {
        $team->members()->detach($member->id);
    }

    public function clearLeaders(Team $team, ?int $keepUserId = null): void
    {
        $team->members()
            ->newPivotStatement()
            ->where('team_id', $team->id)
            ->when($keepUserId !== null, fn ($query) => $query->where('user_id', '!=', $keepUserId))
            ->update(['is_leader' => false, 'updated_at' => now()]);
    }

    public function loadDetails(Team $team): Team
    {
        return $team->load(['members', 'creator'])->loadCount('members');
    }
}
