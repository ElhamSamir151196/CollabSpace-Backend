<?php

namespace App\Interfaces\Repositories;

use App\Enums\TaskStatus;
use App\Filters\QueryFilter;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<Task>
 */
interface TaskRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Tasks list across the projects the user can see.
     *
     * @return LengthAwarePaginator<int, Task>
     */
    public function paginateVisibleTo(User $user, QueryFilter $filter, int $perPage = 15): LengthAwarePaginator;

    /**
     * All tasks of a project for the Kanban board, in column order.
     *
     * @return Collection<int, Task>
     */
    public function forBoard(Project $project, QueryFilter $filter): Collection;

    /**
     * Position for a new card at the bottom of a column.
     */
    public function nextPosition(Project $project, TaskStatus $status): int;

    /**
     * Puts the task at $position (0 = top) of its current status column and
     * renumbers the other cards. Null puts it at the bottom.
     */
    public function placeInColumn(Task $task, ?int $position = null): void;

    /**
     * @param  list<int>  $userIds
     * @return list<int> ids of the users that were newly assigned
     */
    public function syncAssignees(Task $task, array $userIds): array;

    public function hasTasksForTeam(Project $project, Team $team): bool;

    /**
     * Loads everything the task details screen needs.
     */
    public function loadDetails(Task $task): Task;
}
