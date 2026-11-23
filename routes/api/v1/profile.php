<?php

use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Profile (the signed-in user)
|--------------------------------------------------------------------------
|
| Prefix: /api/v1/profile     Names: v1.profile.*
|
*/

Route::prefix('profile')->name('profile.')->middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/', [ProfileController::class, 'show'])->name('show');
    Route::put('/', [ProfileController::class, 'update'])->name('update');
    Route::post('avatar', [ProfileController::class, 'updateAvatar'])->name('avatar.update');
    Route::delete('avatar', [ProfileController::class, 'destroyAvatar'])->name('avatar.destroy');
    Route::put('password', [ProfileController::class, 'updatePassword'])->name('password.update');
});
