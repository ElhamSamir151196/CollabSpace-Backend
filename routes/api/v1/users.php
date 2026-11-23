<?php

use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Users directory
|--------------------------------------------------------------------------
|
| Prefix: /api/v1/users     Names: v1.users.*
|
*/

Route::prefix('users')->name('users.')->middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('{user}', [UserController::class, 'show'])->name('show');
});
