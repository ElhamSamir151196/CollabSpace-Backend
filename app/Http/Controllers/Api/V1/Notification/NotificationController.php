<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Enums\NotificationKind;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\NotificationResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The bell: the signed-in user's notifications, newest first.
 * A notification id from another user is simply "not found".
 */
class NotificationController extends Controller
{
    /**
     * ?unread=1  ?kind=task_assigned  ?per_page=
     */
    public function index(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $request->validate([
            'unread' => ['nullable', 'boolean'],
            'kind' => ['nullable', Rule::enum(NotificationKind::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $notifications = $user->notifications()
            ->when($request->boolean('unread'), fn ($query) => $query->whereNull('read_at'))
            ->when($request->filled('kind'), fn ($query) => $query->where('type', $request->string('kind')->toString()))
            ->paginate(max(1, min($request->integer('per_page', 20), 100)));

        return ApiResponse::success(
            NotificationResource::collection($notifications),
            meta: ['unread_count' => $user->unreadNotifications()->count()],
        );
    }

    public function destroy(string $notification, #[CurrentUser] User $user): JsonResponse
    {
        $user->notifications()->findOrFail($notification)->delete();

        return ApiResponse::message(__('Notification deleted.'));
    }
}
