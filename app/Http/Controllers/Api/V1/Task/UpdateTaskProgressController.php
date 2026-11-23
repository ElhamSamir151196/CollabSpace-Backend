<?php

namespace App\Http\Controllers\Api\V1\Task;

use App\Actions\Task\UpdateTaskProgressAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Task\UpdateTaskProgressRequest;
use App\Http\Resources\V1\TaskResource;
use App\Interfaces\Repositories\TaskRepositoryInterface;
use App\Models\Task;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class UpdateTaskProgressController extends Controller
{
    public function __invoke(
        UpdateTaskProgressRequest $request,
        Task $task,
        UpdateTaskProgressAction $action,
        TaskRepositoryInterface $tasks,
    ): JsonResponse {
        $task = $action->handle($task, $request->integer('progress'));

        return ApiResponse::success(new TaskResource($tasks->loadDetails($task)), __('Progress updated.'));
    }
}
