<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

/**
 * The rules themselves live in Conversation::isAccessibleBy()
 * (also used for the real-time channel authorization).
 */
class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->isAccessibleBy($user);
    }

    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $conversation->isAccessibleBy($user);
    }
}
