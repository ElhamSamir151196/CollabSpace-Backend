<?php

namespace App\Actions\Chat;

use App\Interfaces\Repositories\ConversationRepositoryInterface;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Opens the 1:1 chat between two people: returns the existing one,
 * or creates it the first time. Two people never get two chats.
 */
class StartDirectConversationAction
{
    public function __construct(private readonly ConversationRepositoryInterface $conversations) {}

    public function handle(User $me, User $other): Conversation
    {
        $existing = $this->conversations->findDirect($me, $other);

        if ($existing !== null) {
            return $existing;
        }

        try {
            return DB::transaction(fn () => $this->conversations->createDirect($me, $other));
        } catch (UniqueConstraintViolationException) {
            // Both people opened the chat at the same moment: use the one that won.
            return $this->conversations->findDirect($me, $other) ?? throw new \LogicException('Direct conversation missing.');
        }
    }
}
