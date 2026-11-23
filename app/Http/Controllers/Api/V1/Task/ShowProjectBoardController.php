<?php

namespace App\Http\Controllers\Api\V1\Task;

use App\Enums\TaskStatus;
use App\Filters\TaskFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Task\IndexTasksRequest;
use App\Http\Resources\V1\TaskResource;
use App\Interfaces\Repositories\TaskRepositoryInterface;
use App\Models\Project;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Kanban board of a project: the 4 columns in order, each with its cards.
 * Supports the same filters as the tasks list (team, My Tasks, search, priority ...).
 */
class ShowProjectBoardController extends Controller
{
    public function __invoke(IndexTasksRequest $request, Project $project, TaskRepositoryInterface $tasks): JsonResponse
    {
        Gate::authorize('view', $project);

        $cards = $tasks->forBoard($project, new TaskFilter([...$request->filters(), 'sort' => 'position']));
        $byStatus = $cards->groupBy(fn ($task) => $task->status->value);

        $columns = array_map(function (TaskStatus $status) use ($byStatus, $request) {
            $columnTasks = $byStatus->get($status->value, collect());

            return [
                'status' => $status->toArray(),
                'count' => $columnTasks->count(),
                'tasks' => TaskResource::collection($columnTasks)->resolve($request),
            ];
        }, TaskStatus::cases());

        return ApiResponse::success([
            'project' => ['id' => $project->id, 'slug' => $project->slug, 'name' => $project->name],
            'columns' => $columns,
        ]);
    }
}
