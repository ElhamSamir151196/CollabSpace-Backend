<?php

namespace App\Actions\Chat;

use App\Events\Chat\MessageDeleted;
use App\Interfaces\Repositories\MessageRepositoryInterface;
use App\Models\Message;
use App\Models\User;

class DeleteMessageAction
{
    public function __construct(private readonly MessageRepositoryInterface $messages) {}

    /**
     * Soft delete: the message disappears from the chat. Its thread replies stay
     * in the database but are no longer reachable from the timeline.
     */
    public function handle(Message $message, User $actor): void
    {
        $this->messages->delete($message);

        if (! $message->isAuthoredBy($actor)) {
            activity('chat')->causedBy($actor)->performedOn($message)->event('moderated')
                ->withProperties(['conversation_id' => $message->conversation_id])
                ->log('Message deleted by a moderator');
        }

        broadcast(new MessageDeleted($message->id, $message->conversation_id, $message->parent_id))->toOthers();
    }
}
