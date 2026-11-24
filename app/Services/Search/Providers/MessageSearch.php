<?php

namespace App\Services\Search\Providers;

use App\DTOs\Search\SearchResult;
use App\Interfaces\Search\SearchProvider;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Support\Like;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Chat messages in the conversations the user can open.
 */
class MessageSearch implements SearchProvider
{
    public function key(): string
    {
        return 'messages';
    }

    public function search(User $user, string $term, int $limit): array
    {
        $query = Message::query()
            ->whereHas('conversation', fn (Builder $conversations) => $conversations->accessibleBy($user))
            ->with(['author', 'conversation.participants']);

        Like::any($query, ['body'], $term);

        return $query
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (Message $message) => new SearchResult(
                type: 'message',
                id: $message->id,
                title: Str::limit((string) $message->body, 80),
                subtitle: ($message->author->name ?? __('Deleted user')).' · '.$this->conversationName($message->conversation, $user),
                meta: ['conversation_id' => $message->conversation_id, 'parent_id' => $message->parent_id],
            ))
            ->all();
    }

    private function conversationName(Conversation $conversation, User $viewer): string
    {
        if (! $conversation->isDirect()) {
            return (string) $conversation->name;
        }

        return (string) $conversation->participants->first(fn (User $user) => $user->id !== $viewer->id)?->name;
    }
}
