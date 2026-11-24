<?php

namespace App\Repositories\Eloquent;

use App\Enums\TaskStatus;
use App\Filters\QueryFilter;
use App\Interfaces\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Closure;
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
            ->withCount(['teams', 'guests', ...$this->taskCounts(withEveryStatus: false)]);

        $filter->apply($query);

        return $query->paginate(max(1, min($perPage, static::MAX_PER_PAGE)));
    }

    public function slugExists(string $slug): bool
    {
        return $this->query()->withTrashed()->where('slug', $slug)->exists();
    }

    public function attachTeams(Project $project, array $teamIds): array
    {
        return array_map('intval', $project->teams()->syncWithoutDetaching($teamIds)['attached']);
    }

    public function detachTeam(Project $project, Team $team): void
    {
        $project->teams()->detach($team->id);
    }

    public function attachGuests(Project $project, array $userIds, User $addedBy): array
    {
        $changes = $project->guests()->syncWithoutDetaching(
            array_fill_keys($userIds, ['added_by' => $addedBy->id]),
        );

        return array_map('intval', $changes['attached']);
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
            ->loadCount(['teams', 'guests', ...$this->taskCounts(withEveryStatus: true)]);
    }

    /**
     * tasks_count + completed_tasks_count (for the progress %), and on the
     * details screen also pending / in_progress / in_review counts.
     *
     * @return array<string, Closure>|array<int, string>
     */
    private function taskCounts(bool $withEveryStatus): array
    {
        $statuses = $withEveryStatus ? TaskStatus::cases() : [TaskStatus::Completed];

        $counts = ['tasks'];

        foreach ($statuses as $status) {
            $counts["tasks as {$status->value}_tasks_count"] = fn (Builder $query) => $query->where('status', $status->value);
        }

        return $counts;
    }
}
