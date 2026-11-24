<?php

use App\Http\Controllers\Api\V1\Notification\MarkAllNotificationsReadController;
use App\Http\Controllers\Api\V1\Notification\MarkNotificationReadController;
use App\Http\Controllers\Api\V1\Notification\NotificationController;
use App\Http\Controllers\Api\V1\Notification\UnreadNotificationsCountController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Notifications (the bell)
|--------------------------------------------------------------------------
|
| /api/v1/notifications    Names: v1.notifications.*
| New notifications are also pushed live on private-App.Models.User.{id}.
|
*/

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    // Static paths before {notification}.
    Route::get('notifications/unread-count', UnreadNotificationsCountController::class)->name('notifications.unread-count');
    Route::patch('notifications/read-all', MarkAllNotificationsReadController::class)->name('notifications.read-all');

    Route::apiResource('notifications', NotificationController::class)->only(['index', 'destroy']);
    Route::patch('notifications/{notification}/read', MarkNotificationReadController::class)->name('notifications.read');
});
