<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| This file only loads the feature route files.
| Every feature defines its own prefix, middleware and names inside its file.
|
| Final URLs: /api/v1/...      Route names: v1.*
|
*/

Route::prefix('v1')->name('v1.')->middleware('throttle:api')->group(function () {
    require __DIR__.'/api/v1/health.php';
    require __DIR__.'/api/v1/auth.php';
    require __DIR__.'/api/v1/profile.php';
    require __DIR__.'/api/v1/users.php';
    require __DIR__.'/api/v1/teams.php';
    require __DIR__.'/api/v1/projects.php';
    require __DIR__.'/api/v1/tasks.php';
    require __DIR__.'/api/v1/files.php';
    require __DIR__.'/api/v1/chat.php';
    require __DIR__.'/api/v1/meetings.php';
    require __DIR__.'/api/v1/notifications.php';
    require __DIR__.'/api/v1/search.php';
    require __DIR__.'/api/v1/dashboard.php';
    require __DIR__.'/api/v1/reports.php';
    require __DIR__.'/api/v1/ai.php';

    // Admin
    require __DIR__.'/api/v1/admin/users.php';
    require __DIR__.'/api/v1/admin/activity-log.php';
});
