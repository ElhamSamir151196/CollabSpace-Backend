<?php

use App\Http\Controllers\Api\V1\Team\TeamController;
use App\Http\Controllers\Api\V1\Team\TeamMemberController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Teams
|--------------------------------------------------------------------------
|
| /api/v1/teams                        Names: v1.teams.*
| /api/v1/teams/{team}/members         Names: v1.teams.members.*
| Access: TeamPolicy.
|
*/

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::apiResource('teams', TeamController::class);

    Route::apiResource('teams.members', TeamMemberController::class)
        ->except('show')
        ->scoped();
});
