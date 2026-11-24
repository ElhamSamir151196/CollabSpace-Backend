<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Interfaces\Repositories\ConversationRepositoryInterface;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/**
 * GET /conversations/unread-count — the badge on "Chats" in the navigation.
 */
class UnreadCountController extends Controller
{
    public function __invoke(#[CurrentUser] User $user, ConversationRepositoryInterface $conversations): JsonResponse
    {
        return ApiResponse::success(['unread_count' => $conversations->unreadTotal($user)]);
    }
}
