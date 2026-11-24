<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Actions\Chat\StartDirectConversationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Chat\StartDirectConversationRequest;
use App\Http\Resources\V1\ConversationResource;
use App\Interfaces\Repositories\ConversationRepositoryInterface;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/**
 * POST /conversations/direct { user_id } — get or create the 1:1 chat.
 */
class StartDirectConversationController extends Controller
{
    public function __invoke(
        StartDirectConversationRequest $request,
        #[CurrentUser] User $user,
        StartDirectConversationAction $action,
        ConversationRepositoryInterface $conversations,
    ): JsonResponse {
        $other = User::query()->findOrFail($request->integer('user_id'));

        $conversation = $action->handle($user, $other);

        return ApiResponse::success(new ConversationResource($conversations->loadForDisplay($conversation, $user)));
    }
}
