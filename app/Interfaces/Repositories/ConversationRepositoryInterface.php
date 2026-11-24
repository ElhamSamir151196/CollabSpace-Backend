<?php

namespace App\Interfaces\Repositories;

use App\Enums\ConversationType;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * @extends BaseRepositoryInterface<Conversation>
 */
interface ConversationRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * The chats list: every conversation the user can open, most recent first,
     * each with its last message and the user's unread count.
     *
     * @return LengthAwarePaginator<int, Conversation>
     */
    public function inboxFor(User $user, ?ConversationType $type = null, ?int $projectId = null, int $perPage = 30): LengthAwarePaginator;

    public function findDirect(User $first, User $second): ?Conversation;

    public function createDirect(User $first, User $second): Conversation;

    /**
     * Moves the user's "read up to" marker forward (never backwards).
     */
    public function markRead(Conversation $conversation, User $user, int $messageId): void;

    public function lastMessageId(Conversation $conversation): ?int;

    public function touchLastMessage(Conversation $conversation, Carbon $at): void;

    /**
     * Total unread messages across every conversation (navigation badge).
     */
    public function unreadTotal(User $user): int;

    public function loadForDisplay(Conversation $conversation, User $viewer): Conversation;
}
