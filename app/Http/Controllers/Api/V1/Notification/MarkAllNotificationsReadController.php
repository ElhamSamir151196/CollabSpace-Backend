<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/**
 * PATCH /notifications/read-all
 */
class MarkAllNotificationsReadController extends Controller
{
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $user->unreadNotifications()->update(['read_at' => now()]);

        return ApiResponse::message(__('All notifications marked as read.'));
    }
}
