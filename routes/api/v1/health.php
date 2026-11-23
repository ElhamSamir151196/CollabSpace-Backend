<?php

use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health
|--------------------------------------------------------------------------
|
| GET /api/v1/health  — public, lets the frontend check that the API is up.
|
*/

Route::get('health', HealthController::class)->name('health');
