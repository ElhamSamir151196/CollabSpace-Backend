<?php

use App\Http\Controllers\Api\V1\Files\DownloadFileController;
use App\Http\Controllers\Api\V1\Files\FileController;
use App\Http\Controllers\Api\V1\Files\ProjectFileController;
use App\Http\Controllers\Api\V1\Files\RecentFilesController;
use App\Http\Controllers\Api\V1\Files\TaskFileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Files
|--------------------------------------------------------------------------
|
| /api/v1/projects/{project:slug}/files   Names: v1.projects.files.*
| /api/v1/tasks/{task}/files              Names: v1.tasks.files.*
| /api/v1/files/recent                    Name:  v1.files.recent
| /api/v1/files/{file}                    Names: v1.files.*
| /api/v1/files/{file}/download           Name:  v1.files.download (signed link, no token)
| Access: AttachmentPolicy.
|
*/

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::apiResource('projects.files', ProjectFileController::class)->only(['index', 'store']);
    Route::apiResource('tasks.files', TaskFileController::class)->only(['index', 'store']);

    // Before {file}, so "recent" is not read as a file id.
    Route::get('files/recent', RecentFilesController::class)->name('files.recent');
    Route::apiResource('files', FileController::class)->only(['show', 'destroy']);
});

Route::get('files/{file}/download', DownloadFileController::class)
    ->middleware('signed')
    ->name('files.download');
