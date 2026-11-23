<?php

use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\GoogleLoginController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
|
| Prefix: /api/v1/auth     Names: v1.auth.*
|
| Public endpoints are limited to 5 requests / minute per email + IP.
|
*/

Route::prefix('auth')->name('auth.')->group(function () {

    Route::middleware('throttle:auth')->group(function () {
        Route::post('register', RegisterController::class)->name('register');
        Route::post('login', LoginController::class)->name('login');
        Route::post('google', GoogleLoginController::class)->name('google');
        Route::post('forgot-password', ForgotPasswordController::class)->name('password.forgot');
        Route::post('reset-password', ResetPasswordController::class)->name('password.reset');
    });

    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::post('logout', LogoutController::class)->name('logout');
        Route::get('me', MeController::class)->name('me');
    });

});
