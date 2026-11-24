<?php

namespace App\Actions\Chat;

use App\Actions\Files\UploadAttachmentsAction;
use App\DTOs\Chat\SendMessageData;
use App\Events\Chat\MessageSent;
use App\Interfaces\Repositories\ConversationRepositoryInterface;
use App\Interfaces\Repositories\MessageRepositoryInterface;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Saves a message (text and/or files, optional thread parent and mentions),
 * moves the conversation to the top of the list, marks it read for the sender,
 * then pushes it live to the others.
 */
class SendMessageAction
{
    public function __construct(
        private readonly MessageRepositoryInterface $messages,
        private readonly ConversationRepositoryInterface $conversations,
        private readonly UploadAttachmentsAction $uploadFiles,
    ) {}

    public function handle(Conversation $conversation, SendMessageData $data, User $sender): Message
    {
        $message = DB::transaction(function () use ($conversation, $data, $sender): Message {
            $message = $this->messages->create([
                'conversation_id' => $conversation->id,
                'user_id' => $sender->id,
                'parent_id' => $data->parentId,
                'body' => $data->body,
            ]);

            $this->messages->syncMentions($message, $data->mentionIds);

            if ($data->files !== []) {
                $this->uploadFiles->handle($message->setRelation('conversation', $conversation), $data->files, $sender);
            }

            $this->conversations->touchLastMessage($conversation, $message->created_at ?? now());
            $this->conversations->markRead($conversation, $sender, $message->id);

            return $message;
        });

        broadcast(new MessageSent($message))->toOthers();

        return $message;
    }
}
