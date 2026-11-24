<?php

use App\Http\Controllers\Api\V1\Dashboard\ShowDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
|
| /api/v1/dashboard?project_id=&year=     Name: v1.dashboard
|
*/

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('dashboard', ShowDashboardController::class)->name('dashboard');
});
