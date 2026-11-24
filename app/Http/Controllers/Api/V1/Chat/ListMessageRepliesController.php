<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\MessageResource;
use App\Interfaces\Repositories\MessageRepositoryInterface;
use App\Models\Message;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The thread panel ("#Screen edit"): the parent message and its replies, oldest first.
 * GET /messages/{message}/replies
 */
class ListMessageRepliesController extends Controller
{
    public function __invoke(Request $request, Message $message, MessageRepositoryInterface $messages): JsonResponse
    {
        Gate::authorize('view', $message);

        $replies = $messages->replies($message, $request->integer('per_page', 30));
        foreach ($replies->items() as $reply) {
            $reply->setRelation('conversation', $message->conversation);
        }

        return ApiResponse::success(
            MessageResource::collection($replies),
            meta: ['parent' => (new MessageResource($messages->loadForDisplay($message)))->resolve($request)],
        );
    }
}
