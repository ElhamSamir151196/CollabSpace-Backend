<?php

namespace App\Repositories\Eloquent;

use App\Enums\TaskStatus;
use App\Filters\QueryFilter;
use App\Interfaces\Repositories\TaskRepositoryInterface;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<Task>
 */
class TaskRepository extends BaseRepository implements TaskRepositoryInterface
{
    /**
     * What a Kanban card / list row shows.
     *
     * @var list<string>
     */
    private const CARD_RELATIONS = ['project', 'team', 'reviewer', 'assignees'];

    public function __construct(Task $model)
    {
        parent::__construct($model);
    }

    public function paginateVisibleTo(User $user, QueryFilter $filter, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query()->visibleTo($user)->with(self::CARD_RELATIONS)->withCount('attachments');

        $filter->apply($query);

        return $query->paginate(max(1, min($perPage, static::MAX_PER_PAGE)));
    }

    public function forBoard(Project $project, QueryFilter $filter): Collection
    {
        $query = $this->query()->where('project_id', $project->id)->with(self::CARD_RELATIONS)->withCount('attachments');

        $filter->apply($query);

        return $query->orderBy('id')->get();
    }

    public function nextPosition(Project $project, TaskStatus $status): int
    {
        $max = $this->query()
            ->where('project_id', $project->id)
            ->where('status', $status->value)
            ->max('position');

        return $max === null ? 0 : (int) $max + 1;
    }

    public function placeInColumn(Task $task, ?int $position = null): void
    {
        /** @var list<int> $ids */
        $ids = $this->query()
            ->where('project_id', $task->project_id)
            ->where('status', $task->status->value)
            ->whereKeyNot($task->id)
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $index = $position === null ? count($ids) : max(0, min($position, count($ids)));
        array_splice($ids, $index, 0, [$task->id]);

        foreach ($ids as $newPosition => $id) {
            // Plain update: renumbering is not worth an activity-log entry per card.
            $this->query()->whereKey($id)->where('position', '!=', $newPosition)->toBase()->update(['position' => $newPosition]);
        }

        $task->position = $index;
        $task->syncOriginalAttribute('position');
    }

    public function syncAssignees(Task $task, array $userIds): array
    {
        $changes = $task->assignees()->sync($userIds);

        /** @var list<int> $attached */
        $attached = array_map('intval', $changes['attached']);

        return $attached;
    }

    public function hasTasksForTeam(Project $project, Team $team): bool
    {
        return $this->query()->where('project_id', $project->id)->where('team_id', $team->id)->exists();
    }

    public function loadDetails(Task $task): Task
    {
        return $task->load([...self::CARD_RELATIONS, 'creator'])->loadCount('attachments');
    }
}
