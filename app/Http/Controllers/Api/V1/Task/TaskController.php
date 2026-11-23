<?php

namespace App\Http\Controllers\Api\V1\Task;

use App\Actions\Task\DeleteTaskAction;
use App\Actions\Task\UpdateTaskAction;
use App\DTOs\Task\UpdateTaskData;
use App\Filters\TaskFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Task\IndexTasksRequest;
use App\Http\Requests\V1\Task\UpdateTaskRequest;
use App\Http\Resources\V1\TaskResource;
use App\Interfaces\Repositories\TaskRepositoryInterface;
use App\Models\Task;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * /tasks: all tasks the user can see (across projects) + a single task.
 * Tasks are created inside a project: POST /projects/{project}/tasks.
 */
class TaskController extends Controller
{
    public function __construct(private readonly TaskRepositoryInterface $tasks) {}

    public function index(IndexTasksRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $tasks = $this->tasks->paginateVisibleTo(
            $user,
            new TaskFilter($request->filters()),
            $request->integer('per_page', 15),
        );

        return ApiResponse::success(TaskResource::collection($tasks));
    }

    public function show(Task $task): JsonResponse
    {
        Gate::authorize('view', $task);

        return ApiResponse::success(new TaskResource($this->tasks->loadDetails($task)));
    }

    public function update(UpdateTaskRequest $request, Task $task, #[CurrentUser] User $user, UpdateTaskAction $action): JsonResponse
    {
        $task = $action->handle($task, UpdateTaskData::fromRequest($request), $user);

        return ApiResponse::success(new TaskResource($this->tasks->loadDetails($task)), __('Task updated successfully.'));
    }

    public function destroy(Task $task, DeleteTaskAction $action): JsonResponse
    {
        Gate::authorize('delete', $task);

        $action->handle($task);

        return ApiResponse::message(__('Task deleted successfully.'));
    }
}
