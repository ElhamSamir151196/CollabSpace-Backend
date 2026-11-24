<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\NotificationResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/**
 * PATCH /notifications/{notification}/read
 */
class MarkNotificationReadController extends Controller
{
    public function __invoke(string $notification, #[CurrentUser] User $user): JsonResponse
    {
        $model = $user->notifications()->findOrFail($notification);
        $model->markAsRead();

        return ApiResponse::success(new NotificationResource($model));
    }
}
