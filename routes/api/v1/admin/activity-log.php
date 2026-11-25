<?php

use App\Http\Controllers\Api\V1\Admin\ActivityLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin > Activity log
|--------------------------------------------------------------------------
|
| Prefix: /api/v1/admin/activity-log     Names: v1.admin.activity-log.*
| Access: permission "activity-log.view" (admins).
|
*/

Route::prefix('admin')->name('admin.')->middleware(['auth:sanctum', 'active'])->group(function () {
    Route::apiResource('activity-log', ActivityLogController::class)
        ->only(['index', 'show'])
        ->parameters(['activity-log' => 'activity']);
});
