<?php

namespace App\Actions\Chat;

use App\Events\Chat\ConversationRead;
use App\Interfaces\Repositories\ConversationRepositoryInterface;
use App\Models\Conversation;
use App\Models\User;

/**
 * "I have read up to this message" (defaults to the latest one).
 * Clears the unread badge and sends a read receipt to the others.
 */
class MarkConversationReadAction
{
    public function __construct(private readonly ConversationRepositoryInterface $conversations) {}

    public function handle(Conversation $conversation, User $user, ?int $messageId = null): void
    {
        $messageId ??= $this->conversations->lastMessageId($conversation);

        if ($messageId === null) {
            return;
        }

        $this->conversations->markRead($conversation, $user, $messageId);

        broadcast(new ConversationRead($conversation->id, $user->id, $messageId))->toOthers();
    }
}
