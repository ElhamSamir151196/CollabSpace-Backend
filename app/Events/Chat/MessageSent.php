<?php

namespace App\Events\Chat;

use App\Http\Resources\V1\MessageResource;
use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A new message (or thread reply).
 *  - "conversation.{id}": everyone who has the chat open.
 *  - "App.Models.User.{id}": each person of a direct chat, so their
 *    chats list / unread badge updates even when the chat is not open.
 * Event name on the frontend: ".message.sent".
 */
class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Message $message) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $conversation = $this->message->conversation;
        $channels = [new PrivateChannel("conversation.{$conversation->id}")];

        if ($conversation->isDirect()) {
            foreach ($conversation->participants()->pluck('users.id') as $userId) {
                $channels[] = new PrivateChannel("App.Models.User.{$userId}");
            }
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return ['message' => (new MessageResource($this->message->loadMissing(['author', 'mentions', 'attachments.uploader'])))->resolve()];
    }
}
