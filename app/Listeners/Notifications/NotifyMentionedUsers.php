<?php

namespace App\Listeners\Notifications;

use App\Events\Chat\MessageSent;
use App\Notifications\MentionedInMessageNotification;
use App\Services\Notifications\Recipients;
use Illuminate\Support\Facades\Notification;

/**
 * "@Sara" in a chat message notifies Sara.
 * (Plain new messages are not notifications: the chat has its own unread badges.)
 */
class NotifyMentionedUsers
{
    public function __construct(private readonly Recipients $recipients) {}

    public function handle(MessageSent $event): void
    {
        $message = $event->message;
        $author = $message->author;

        if ($author === null) {
            return;
        }

        Notification::send(
            $this->recipients->of($message->mentions()->pluck('users.id'), except: $author),
            new MentionedInMessageNotification($message, $author),
        );
    }
}
