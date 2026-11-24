<?php

namespace App\Http\Requests\Concerns;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Validation\Validator;

/**
 * "mention_ids": users mentioned in a message. Each one must be able to
 * read the conversation (you can't mention someone who can't see the chat).
 */
trait ValidatesMentions
{
    /**
     * @return array<string, array<mixed>>
     */
    protected function mentionRules(): array
    {
        return [
            'mention_ids' => ['sometimes', 'array', 'max:20'],
            'mention_ids.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return list<int>
     */
    public function mentionIds(): array
    {
        return array_values(array_unique(array_map('intval', (array) $this->validated('mention_ids', []))));
    }

    protected function ensureMentionsCanReadConversation(Validator $validator, Conversation $conversation): void
    {
        if ($validator->errors()->hasAny(['mention_ids', 'mention_ids.*'])) {
            return;
        }

        $ids = $this->mentionIds();

        if ($ids === []) {
            return;
        }

        $users = User::query()->whereKey($ids)->where('is_active', true)->get();

        $allowed = $users->count() === count($ids)
            && $users->every(fn (User $user) => $conversation->isAccessibleBy($user));

        if (! $allowed) {
            $validator->errors()->add('mention_ids', __('You can only mention people who are in this conversation.'));
        }
    }
}
