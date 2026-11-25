<?php

use App\Http\Controllers\Api\V1\Profile\ChangePasswordController;
use App\Http\Controllers\Api\V1\Profile\DeleteAvatarController;
use App\Http\Controllers\Api\V1\Profile\ListMyFilesController;
use App\Http\Controllers\Api\V1\Profile\ProfileController;
use App\Http\Controllers\Api\V1\Profile\UpdateAvatarController;
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

    Route::post('avatar', UpdateAvatarController::class)->middleware('throttle:uploads')->name('avatar.update');
    Route::delete('avatar', DeleteAvatarController::class)->name('avatar.destroy');
    Route::put('password', ChangePasswordController::class)->name('password.update');
    Route::get('files', ListMyFilesController::class)->name('files');
});
