<?php

use App\Http\Controllers\Api\V1\Project\ListProjectMembersController;
use App\Http\Controllers\Api\V1\Project\ProjectController;
use App\Http\Controllers\Api\V1\Project\ProjectGuestController;
use App\Http\Controllers\Api\V1\Project\ProjectTeamController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Projects
|--------------------------------------------------------------------------
|
| /api/v1/projects/{project:slug}              Names: v1.projects.*
| /api/v1/projects/{project}/teams/{team}      Names: v1.projects.teams.*
| /api/v1/projects/{project}/guests/{guest}    Names: v1.projects.guests.*
| /api/v1/projects/{project}/members           Name:  v1.projects.members
| Access: ProjectPolicy.
|
*/

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::apiResource('projects', ProjectController::class);

    Route::apiResource('projects.teams', ProjectTeamController::class)
        ->only(['index', 'store', 'destroy'])
        ->scoped(['project' => 'slug', 'team' => 'id']);

    Route::apiResource('projects.guests', ProjectGuestController::class)
        ->only(['index', 'store', 'destroy'])
        ->scoped(['project' => 'slug', 'guest' => 'id']);

    Route::get('projects/{project}/members', ListProjectMembersController::class)->name('projects.members');
});
