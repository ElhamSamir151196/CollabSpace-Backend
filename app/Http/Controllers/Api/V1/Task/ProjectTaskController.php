<?php

namespace App\Http\Controllers\Api\V1\Task;

use App\Actions\Task\CreateTaskAction;
use App\DTOs\Task\CreateTaskData;
use App\Filters\TaskFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Task\IndexTasksRequest;
use App\Http\Requests\V1\Task\StoreTaskRequest;
use App\Http\Resources\V1\TaskResource;
use App\Interfaces\Repositories\TaskRepositoryInterface;
use App\Models\Project;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Tasks of one project: /projects/{project}/tasks (list + create).
 * The grouped Kanban view is ShowProjectBoardController.
 */
class ProjectTaskController extends Controller
{
    public function __construct(private readonly TaskRepositoryInterface $tasks) {}

    public function index(IndexTasksRequest $request, Project $project, #[CurrentUser] User $user): JsonResponse
    {
        Gate::authorize('view', $project);

        $tasks = $this->tasks->paginateVisibleTo(
            $user,
            new TaskFilter([...$request->filters(), 'project_id' => $project->id]),
            $request->integer('per_page', 15),
        );

        return ApiResponse::success(TaskResource::collection($tasks));
    }

    public function store(StoreTaskRequest $request, Project $project, #[CurrentUser] User $user, CreateTaskAction $action): JsonResponse
    {
        $task = $action->handle($project, CreateTaskData::fromRequest($request), $user);

        return ApiResponse::created(new TaskResource($this->tasks->loadDetails($task)), __('Task created successfully.'));
    }
}
