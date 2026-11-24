<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/**
 * GET /notifications/unread-count — the red dot on the bell.
 */
class UnreadNotificationsCountController extends Controller
{
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        return ApiResponse::success(['unread_count' => $user->unreadNotifications()->count()]);
    }
}
