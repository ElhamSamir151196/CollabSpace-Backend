<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Actions\Ai\SendAiMessageAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AiMessageResource;
use App\Models\AiConversation;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * POST /ai/conversations/{conversation}/messages { content } — returns the question and the answer.
 */
class SendAiMessageController extends Controller
{
    public function __invoke(Request $request, AiConversation $conversation, #[CurrentUser] User $user, SendAiMessageAction $action): JsonResponse
    {
        Gate::authorize('sendMessage', $conversation);

        $request->validate(['content' => ['required', 'string', 'max:4000']]);

        $turn = $action->handle($conversation, trim($request->string('content')->toString()), $user);

        return ApiResponse::created([
            'question' => (new AiMessageResource($turn['question']))->resolve($request),
            'answer' => (new AiMessageResource($turn['answer']))->resolve($request),
        ]);
    }
}
