<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Actions\Ai\SendAiMessageAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AiConversationResource;
use App\Models\AiConversation;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * "Collabspace AI" page: the user's private AI chats.
 * Sending a message is SendAiMessageController.
 */
class AiConversationController extends Controller
{
    public function index(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $conversations = AiConversation::query()
            ->where('user_id', $user->id)
            ->with('latestMessage')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(max(1, min($request->integer('per_page', 20), 100)));

        return ApiResponse::success(AiConversationResource::collection($conversations));
    }

    /**
     * { message? } — creates the chat, and answers the first message when one is sent.
     */
    public function store(Request $request, #[CurrentUser] User $user, SendAiMessageAction $send): JsonResponse
    {
        $request->validate(['message' => ['nullable', 'string', 'max:4000']]);

        $conversation = AiConversation::query()->create([
            'user_id' => $user->id,
            'title' => SendAiMessageAction::DEFAULT_TITLE,
            'last_message_at' => now(),
        ]);

        if ($request->filled('message')) {
            try {
                $send->handle($conversation, trim($request->string('message')->toString()), $user);
            } catch (\Throwable $e) {
                $conversation->delete();

                throw $e;
            }
        }

        return ApiResponse::created(new AiConversationResource($conversation->refresh()->load('messages')));
    }

    public function show(AiConversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        return ApiResponse::success(new AiConversationResource(
            $conversation->load(['messages' => fn ($query) => $query->orderBy('id')]),
        ));
    }

    public function destroy(AiConversation $conversation): JsonResponse
    {
        Gate::authorize('delete', $conversation);

        $conversation->delete();

        return ApiResponse::message(__('Chat deleted.'));
    }
}
