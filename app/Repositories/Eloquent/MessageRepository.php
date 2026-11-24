<?php

namespace App\Repositories\Eloquent;

use App\Interfaces\Repositories\MessageRepositoryInterface;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Contracts\Pagination\CursorPaginator;

/**
 * @extends BaseRepository<Message>
 */
class MessageRepository extends BaseRepository implements MessageRepositoryInterface
{
    /** @var list<string> */
    private const RELATIONS = ['author', 'mentions', 'attachments.uploader'];

    public function __construct(Message $model)
    {
        parent::__construct($model);
    }

    public function timeline(Conversation $conversation, int $perPage = 30): CursorPaginator
    {
        return $this->query()
            ->where('conversation_id', $conversation->id)
            ->whereNull('parent_id')
            ->with(self::RELATIONS)
            ->withCount('replies')
            ->orderByDesc('id')
            ->cursorPaginate($this->limit($perPage));
    }

    public function replies(Message $parent, int $perPage = 30): CursorPaginator
    {
        return $this->query()
            ->where('parent_id', $parent->id)
            ->with(self::RELATIONS)
            ->orderBy('id')
            ->cursorPaginate($this->limit($perPage));
    }

    public function syncMentions(Message $message, array $userIds): void
    {
        $message->mentions()->sync($userIds);
    }

    public function loadForDisplay(Message $message): Message
    {
        return $message->load(self::RELATIONS)->loadCount('replies');
    }

    private function limit(int $perPage): int
    {
        return max(1, min($perPage, static::MAX_PER_PAGE));
    }
}
