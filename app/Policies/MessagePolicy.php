<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Message;
use App\Models\User;

/**
 * - Only the author edits a message.
 * - The author deletes it; in project / team channels the project's manager
 *   and admins can delete any message too (moderation).
 */
class MessagePolicy
{
    public function view(User $user, Message $message): bool
    {
        return $message->conversation->isAccessibleBy($user);
    }

    public function update(User $user, Message $message): bool
    {
        return $message->isAuthoredBy($user) && $message->conversation->isAccessibleBy($user);
    }

    public function delete(User $user, Message $message): bool
    {
        $conversation = $message->conversation;

        if ($message->isAuthoredBy($user)) {
            return $conversation->isAccessibleBy($user);
        }

        if ($conversation->isDirect()) {
            return false;
        }

        return $user->can(Permission::ChatsViewAll->value)
            || $conversation->project?->isManagedBy($user) === true;
    }
}
