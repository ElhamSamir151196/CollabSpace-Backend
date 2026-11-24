<?php

namespace App\Interfaces\Repositories;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Contracts\Pagination\CursorPaginator;

/**
 * @extends BaseRepositoryInterface<Message>
 */
interface MessageRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Main timeline (no thread replies), newest first, cursor paginated.
     *
     * @return CursorPaginator<int, Message>
     */
    public function timeline(Conversation $conversation, int $perPage = 30): CursorPaginator;

    /**
     * Replies of a thread, oldest first.
     *
     * @return CursorPaginator<int, Message>
     */
    public function replies(Message $parent, int $perPage = 30): CursorPaginator;

    /**
     * @param  list<int>  $userIds
     */
    public function syncMentions(Message $message, array $userIds): void;

    public function loadForDisplay(Message $message): Message;
}
