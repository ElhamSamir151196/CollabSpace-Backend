<?php

use App\Http\Controllers\Api\V1\Task\MoveTaskController;
use App\Http\Controllers\Api\V1\Task\ProjectTaskController;
use App\Http\Controllers\Api\V1\Task\ShowProjectBoardController;
use App\Http\Controllers\Api\V1\Task\TaskController;
use App\Http\Controllers\Api\V1\Task\UpdateTaskProgressController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tasks & Kanban
|--------------------------------------------------------------------------
|
| /api/v1/tasks/{task}                    Names: v1.tasks.*
| /api/v1/projects/{project:slug}/tasks   Names: v1.projects.tasks.*
| /api/v1/projects/{project:slug}/board   Name:  v1.projects.board
| Access: TaskPolicy.
|
*/

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::apiResource('tasks', TaskController::class)->except('store');

    Route::patch('tasks/{task}/move', MoveTaskController::class)->name('tasks.move');
    Route::patch('tasks/{task}/progress', UpdateTaskProgressController::class)->name('tasks.progress');

    Route::apiResource('projects.tasks', ProjectTaskController::class)->only(['index', 'store']);

    Route::get('projects/{project}/board', ShowProjectBoardController::class)->name('projects.board');
});
