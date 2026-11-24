<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Actions\Chat\SendMessageAction;
use App\DTOs\Chat\SendMessageData;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Chat\SendMessageRequest;
use App\Http\Resources\V1\MessageResource;
use App\Interfaces\Repositories\MessageRepositoryInterface;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * /conversations/{conversation}/messages
 *  - index: the timeline, newest first, cursor paginated (?cursor=&per_page=).
 *  - store: send a message (JSON or multipart with files[]).
 */
class ConversationMessageController extends Controller
{
    public function __construct(private readonly MessageRepositoryInterface $messages) {}

    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $messages = $this->messages->timeline($conversation, $request->integer('per_page', 30));

        // Every message belongs to this chat: share the instance (no extra queries).
        foreach ($messages->items() as $message) {
            $message->setRelation('conversation', $conversation);
        }

        return ApiResponse::success(MessageResource::collection($messages));
    }

    public function store(
        SendMessageRequest $request,
        Conversation $conversation,
        #[CurrentUser] User $user,
        SendMessageAction $action,
    ): JsonResponse {
        $message = $action->handle($conversation, SendMessageData::fromRequest($request), $user);

        return ApiResponse::created(new MessageResource($this->messages->loadForDisplay($message)), __('Message sent.'));
    }
}
