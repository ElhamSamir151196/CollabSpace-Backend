<?php

namespace App\Repositories\Eloquent;

use App\Filters\QueryFilter;
use App\Interfaces\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<Project>
 */
class ProjectRepository extends BaseRepository implements ProjectRepositoryInterface
{
    public function __construct(Project $model)
    {
        parent::__construct($model);
    }

    public function paginateVisibleTo(User $user, QueryFilter $filter, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query()
            ->visibleTo($user)
            ->with(['manager', 'teams' => fn ($teams) => $teams->withCount('members')])
            ->withCount(['teams', 'guests']);

        $filter->apply($query);

        return $query->paginate(max(1, min($perPage, static::MAX_PER_PAGE)));
    }

    public function slugExists(string $slug): bool
    {
        return $this->query()->withTrashed()->where('slug', $slug)->exists();
    }

    public function attachTeams(Project $project, array $teamIds): void
    {
        $project->teams()->syncWithoutDetaching($teamIds);
    }

    public function detachTeam(Project $project, Team $team): void
    {
        $project->teams()->detach($team->id);
    }

    public function attachGuests(Project $project, array $userIds, User $addedBy): void
    {
        $project->guests()->syncWithoutDetaching(
            array_fill_keys($userIds, ['added_by' => $addedBy->id]),
        );
    }

    public function detachGuest(Project $project, User $guest): void
    {
        $project->guests()->detach($guest->id);
    }

    public function members(Project $project): Collection
    {
        $teamIds = $project->teams()->pluck('teams.id');

        return User::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($project, $teamIds) {
                $query->whereKey($project->manager_id)
                    ->orWhereIn('id', $project->guests()->select('users.id'))
                    ->orWhereHas('teams', fn (Builder $teams) => $teams->whereIn('teams.id', $teamIds));
            })
            ->orderBy('name')
            ->get();
    }

    public function loadDetails(Project $project): Project
    {
        return $project
            ->load(['manager', 'creator', 'teams' => fn ($query) => $query->withCount('members'), 'guests'])
            ->loadCount(['teams', 'guests']);
    }
}
