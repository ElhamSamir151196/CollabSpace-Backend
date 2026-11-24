<?php

namespace App\Notifications;

use App\Enums\NotificationKind;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Str;

class MentionedInMessageNotification extends AppNotification
{
    public function __construct(
        public readonly Message $chatMessage,
        public readonly User $by,
    ) {
        parent::__construct();
    }

    public function kind(): NotificationKind
    {
        return NotificationKind::Mentioned;
    }

    protected function message(): string
    {
        $conversation = $this->chatMessage->conversation;
        $where = $conversation->isDirect() ? __('a direct message') : (string) $conversation->name;

        return __(':name mentioned you in :where: ":text"', [
            'name' => $this->by->name,
            'where' => $where,
            'text' => Str::limit((string) $this->chatMessage->body, 80),
        ]);
    }

    protected function target(): array
    {
        return [
            'type' => 'message',
            'id' => $this->chatMessage->id,
            'conversation_id' => $this->chatMessage->conversation_id,
            'parent_id' => $this->chatMessage->parent_id,
        ];
    }

    protected function actor(): ?User
    {
        return $this->by;
    }
}
