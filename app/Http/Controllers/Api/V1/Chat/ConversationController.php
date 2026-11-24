<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Enums\ConversationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Chat\IndexConversationsRequest;
use App\Http\Resources\V1\ConversationResource;
use App\Interfaces\Repositories\ConversationRepositoryInterface;
use App\Models\Conversation;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * The chats list (Inbox + Project Chats) and a single chat header.
 */
class ConversationController extends Controller
{
    public function __construct(private readonly ConversationRepositoryInterface $conversations) {}

    public function index(IndexConversationsRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $conversations = $this->conversations->inboxFor(
            $user,
            $request->enum('type', ConversationType::class),
            $request->filled('project_id') ? $request->integer('project_id') : null,
            $request->integer('per_page', 30),
        );

        return ApiResponse::success(ConversationResource::collection($conversations));
    }

    public function show(Conversation $conversation, #[CurrentUser] User $user): JsonResponse
    {
        Gate::authorize('view', $conversation);

        return ApiResponse::success(new ConversationResource($this->conversations->loadForDisplay($conversation, $user)));
    }
}
