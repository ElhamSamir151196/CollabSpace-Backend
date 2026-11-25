<?php

use App\Http\Controllers\Api\V1\Reports\DownloadReportExportController;
use App\Http\Controllers\Api\V1\Reports\ProjectsReportController;
use App\Http\Controllers\Api\V1\Reports\ReportExportController;
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
| /api/v1/reports/*?project_id=&from=&to=          Names: v1.reports.*
| /api/v1/reports/exports                           Names: v1.reports.exports.* (PDF)
| /api/v1/reports/exports/{export}/download         Name:  v1.reports.exports.download (signed link)
| Access: ReportPolicy (gates "viewReports" and "viewUserReport"), ReportExportPolicy.
|
*/

Route::prefix('reports')->name('reports.')->group(function () {
    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::get('overview', ReportOverviewController::class)->name('overview');
        Route::get('projects', ProjectsReportController::class)->name('projects');
        Route::get('tasks', TasksReportController::class)->name('tasks');
        Route::get('teams', TeamsReportController::class)->name('teams');
        Route::get('users/{user}', UserReportController::class)->name('users');

        Route::apiResource('exports', ReportExportController::class)->only(['index', 'store', 'destroy'])->middlewareFor('store', 'throttle:exports');
    });

    Route::get('exports/{export}/download', DownloadReportExportController::class)
        ->middleware('signed')
        ->name('exports.download');
});
