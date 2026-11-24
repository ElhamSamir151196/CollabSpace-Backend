<?php

namespace App\Repositories\Eloquent;

use App\Enums\ConversationType;
use App\Interfaces\Repositories\ConversationRepositoryInterface;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * @extends BaseRepository<Conversation>
 */
class ConversationRepository extends BaseRepository implements ConversationRepositoryInterface
{
    public function __construct(Conversation $model)
    {
        parent::__construct($model);
    }

    public function inboxFor(User $user, ?ConversationType $type = null, ?int $projectId = null, int $perPage = 30): LengthAwarePaginator
    {
        $query = $this->query()
            ->accessibleBy($user)
            ->when($type !== null, fn (Builder $query) => $query->where('type', $type?->value))
            ->when($projectId !== null, fn (Builder $query) => $query->where('project_id', $projectId))
            ->with($this->displayRelations())
            ->withCount($this->unreadCount($user))
            // Conversations with messages first (newest on top), then empty ones.
            ->orderByRaw('last_message_at is null')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        return $query->paginate(max(1, min($perPage, static::MAX_PER_PAGE)));
    }

    public function findDirect(User $first, User $second): ?Conversation
    {
        return $this->query()->where('direct_key', Conversation::directKey($first->id, $second->id))->first();
    }

    public function createDirect(User $first, User $second): Conversation
    {
        $conversation = $this->create([
            'type' => ConversationType::Direct,
            'direct_key' => Conversation::directKey($first->id, $second->id),
            'created_by' => $first->id,
        ]);

        $conversation->participants()->attach([$first->id, $second->id]);

        return $conversation;
    }

    public function markRead(Conversation $conversation, User $user, int $messageId): void
    {
        $current = $conversation->participants()->whereKey($user->id)->first();

        if ($current === null) {
            $conversation->participants()->attach($user->id, [
                'last_read_message_id' => $messageId,
                'last_read_at' => now(),
            ]);

            return;
        }

        /** @var int|null $lastRead */
        $lastRead = $current->getRelationValue('pivot')?->last_read_message_id;

        if ($lastRead === null || $messageId > (int) $lastRead) {
            $conversation->participants()->updateExistingPivot($user->id, [
                'last_read_message_id' => $messageId,
                'last_read_at' => now(),
            ]);
        }
    }

    public function lastMessageId(Conversation $conversation): ?int
    {
        $id = $conversation->messages()->max('id');

        return $id === null ? null : (int) $id;
    }

    public function touchLastMessage(Conversation $conversation, Carbon $at): void
    {
        $conversation->forceFill(['last_message_at' => $at])->save();
    }

    public function unreadTotal(User $user): int
    {
        return (int) $this->query()
            ->accessibleBy($user)
            ->withCount($this->unreadCount($user))
            ->get()
            ->sum('unread_count');
    }

    public function loadForDisplay(Conversation $conversation, User $viewer): Conversation
    {
        return $conversation
            ->load($this->displayRelations())
            ->loadCount($this->unreadCount($viewer));
    }

    /**
     * @return list<string>
     */
    private function displayRelations(): array
    {
        return ['project', 'team', 'participants', 'latestMessage.author'];
    }

    /**
     * "unread_count": messages from other people after the user's read marker.
     *
     * @return array<string, \Closure>
     */
    private function unreadCount(User $user): array
    {
        return [
            'messages as unread_count' => fn (Builder $messages) => $messages
                ->where(fn (Builder $query) => $query->whereNull('messages.user_id')->orWhere('messages.user_id', '!=', $user->id))
                ->whereRaw(
                    'messages.id > coalesce((select cu.last_read_message_id from conversation_user cu where cu.conversation_id = messages.conversation_id and cu.user_id = ?), 0)',
                    [$user->id],
                ),
        ];
    }
}
