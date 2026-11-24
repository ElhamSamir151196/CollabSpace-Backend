<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Actions\Chat\MarkConversationReadAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Chat\MarkConversationReadRequest;
use App\Models\Conversation;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/**
 * POST /conversations/{conversation}/read { message_id? }
 */
class MarkConversationReadController extends Controller
{
    public function __invoke(
        MarkConversationReadRequest $request,
        Conversation $conversation,
        #[CurrentUser] User $user,
        MarkConversationReadAction $action,
    ): JsonResponse {
        $action->handle(
            $conversation,
            $user,
            $request->filled('message_id') ? $request->integer('message_id') : null,
        );

        return ApiResponse::message(__('Conversation marked as read.'));
    }
}
