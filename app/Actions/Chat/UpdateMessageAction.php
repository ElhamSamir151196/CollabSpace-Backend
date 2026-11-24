<?php

namespace App\Actions\Chat;

use App\DTOs\Chat\UpdateMessageData;
use App\Events\Chat\MessageUpdated;
use App\Interfaces\Repositories\MessageRepositoryInterface;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

class UpdateMessageAction
{
    public function __construct(private readonly MessageRepositoryInterface $messages) {}

    public function handle(Message $message, UpdateMessageData $data): Message
    {
        $message = DB::transaction(function () use ($message, $data): Message {
            $message = $this->messages->update($message, ['body' => $data->body, 'edited_at' => now()]);

            if ($data->mentionIds !== null) {
                $this->messages->syncMentions($message, $data->mentionIds);
            }

            return $message;
        });

        broadcast(new MessageUpdated($message))->toOthers();

        return $message;
    }
}
