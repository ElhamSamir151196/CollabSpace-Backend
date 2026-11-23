<?php

use App\Http\Controllers\Api\V1\Admin\UserController;
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

Route::prefix('admin/users')->name('admin.users.')->middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::post('/', [UserController::class, 'store'])->name('store');
    Route::get('{user}', [UserController::class, 'show'])->name('show');
    Route::put('{user}', [UserController::class, 'update'])->name('update');
    Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy');
    Route::patch('{user}/role', [UserController::class, 'updateRole'])->name('role.update');
    Route::patch('{user}/status', [UserController::class, 'updateStatus'])->name('status.update');
});
