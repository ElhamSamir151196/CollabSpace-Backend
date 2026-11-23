<?php

use App\Http\Controllers\Api\V1\Admin\User\UpdateUserRoleController;
use App\Http\Controllers\Api\V1\Admin\User\UpdateUserStatusController;
use App\Http\Controllers\Api\V1\Admin\User\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin > Users
|--------------------------------------------------------------------------
|
| Prefix: /api/v1/admin/users     Names: v1.admin.users.*
| Access: UserPolicy (permission "users.manage").
|
*/

Route::prefix('admin')->name('admin.')->middleware(['auth:sanctum', 'active'])->group(function () {
    Route::apiResource('users', UserController::class);

    Route::patch('users/{user}/role', UpdateUserRoleController::class)->name('users.role.update');
    Route::patch('users/{user}/status', UpdateUserStatusController::class)->name('users.status.update');
});
