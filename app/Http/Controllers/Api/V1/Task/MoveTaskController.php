<?php

namespace App\Http\Controllers\Api\V1\Task;

use App\Actions\Task\MoveTaskAction;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Task\MoveTaskRequest;
use App\Http\Resources\V1\TaskResource;
use App\Interfaces\Repositories\TaskRepositoryInterface;
use App\Models\Task;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class MoveTaskController extends Controller
{
    public function __invoke(
        MoveTaskRequest $request,
        Task $task,
        #[CurrentUser] User $user,
        MoveTaskAction $action,
        TaskRepositoryInterface $tasks,
    ): JsonResponse {
        $task = $action->handle(
            $task,
            $request->enum('status', TaskStatus::class) ?? $task->status,
            $request->position(),
            $user,
        );

        return ApiResponse::success(new TaskResource($tasks->loadDetails($task)), __('Task moved.'));
    }
}
