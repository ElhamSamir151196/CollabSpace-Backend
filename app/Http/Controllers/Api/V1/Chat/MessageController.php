<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Actions\Chat\DeleteMessageAction;
use App\Actions\Chat\UpdateMessageAction;
use App\DTOs\Chat\UpdateMessageData;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Chat\UpdateMessageRequest;
use App\Http\Resources\V1\MessageResource;
use App\Interfaces\Repositories\MessageRepositoryInterface;
use App\Models\Message;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Edit / delete a single message: /messages/{message}.
 */
class MessageController extends Controller
{
    public function update(
        UpdateMessageRequest $request,
        Message $message,
        UpdateMessageAction $action,
        MessageRepositoryInterface $messages,
    ): JsonResponse {
        $message = $action->handle($message, UpdateMessageData::fromRequest($request));

        return ApiResponse::success(new MessageResource($messages->loadForDisplay($message)), __('Message updated.'));
    }

    public function destroy(Message $message, #[CurrentUser] User $user, DeleteMessageAction $action): JsonResponse
    {
        Gate::authorize('delete', $message);

        $action->handle($message, $user);

        return ApiResponse::message(__('Message deleted.'));
    }
}
