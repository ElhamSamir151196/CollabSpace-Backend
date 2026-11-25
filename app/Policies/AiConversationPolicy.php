<?php

namespace App\Policies;

use App\Models\AiConversation;
use App\Models\User;

/**
 * AI chats are private: only their owner can see, use or delete them
 * (admins included — nobody reads someone else's AI chat).
 */
class AiConversationPolicy
{
    public function view(User $user, AiConversation $conversation): bool
    {
        return $conversation->isOwnedBy($user);
    }

    public function sendMessage(User $user, AiConversation $conversation): bool
    {
        return $conversation->isOwnedBy($user);
    }

    public function delete(User $user, AiConversation $conversation): bool
    {
        return $conversation->isOwnedBy($user);
    }
}
