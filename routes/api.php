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
});
