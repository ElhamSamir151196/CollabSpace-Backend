<?php

use App\Http\Controllers\Api\V1\Reports\ProjectsReportController;
use App\Http\Controllers\Api\V1\Reports\ReportOverviewController;
use App\Http\Controllers\Api\V1\Reports\TasksReportController;
use App\Http\Controllers\Api\V1\Reports\TeamsReportController;
use App\Http\Controllers\Api\V1\Reports\UserReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Reports
|--------------------------------------------------------------------------
|
| /api/v1/reports/*?project_id=&from=&to=      Names: v1.reports.*
| Access: ReportPolicy (gates "viewReports" and "viewUserReport").
|
*/

Route::prefix('reports')->name('reports.')->middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('overview', ReportOverviewController::class)->name('overview');
    Route::get('projects', ProjectsReportController::class)->name('projects');
    Route::get('tasks', TasksReportController::class)->name('tasks');
    Route::get('teams', TeamsReportController::class)->name('teams');
    Route::get('users/{user}', UserReportController::class)->name('users');
});
