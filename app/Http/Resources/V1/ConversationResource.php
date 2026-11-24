<?php

namespace App\Http\Resources\V1;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * A row of the chats list / the chat header.
 *  - direct:  "with" = the other person; "read_receipts" = how far each person has read.
 *  - project / team: "project" (and "team") so the frontend can group channels by project.
 *
 * @mixin Conversation
 */
class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User|null $viewer */
        $viewer = $request->user();
        $other = $this->isDirect() && $this->relationLoaded('participants')
            ? $this->participants->first(fn (User $user) => $user->id !== $viewer?->id)
            : null;

        return [
            'id' => $this->id,
            'type' => $this->type->toArray(),
            'name' => $this->isDirect() ? $other?->name : $this->name,
            'with' => $this->when($this->isDirect(), fn () => $other !== null ? new UserSummaryResource($other) : null),
            'project' => $this->whenLoaded('project', fn () => $this->project === null ? null : [
                'id' => $this->project->id,
                'slug' => $this->project->slug,
                'name' => $this->project->name,
            ]),
            'team' => $this->whenLoaded('team', fn () => $this->team === null ? null : [
                'id' => $this->team->id,
                'name' => $this->team->name,
                'color' => $this->team->color,
            ]),
            'last_message' => $this->whenLoaded('latestMessage', fn () => $this->latestMessage === null ? null : [
                'id' => $this->latestMessage->id,
                'body' => $this->latestMessage->body !== null ? Str::limit($this->latestMessage->body, 100) : null,
                'author' => $this->latestMessage->author !== null ? [
                    'id' => $this->latestMessage->author->id,
                    'name' => $this->latestMessage->author->name,
                    'avatar_url' => $this->latestMessage->author->avatarUrl(),
                ] : null,
                'created_at' => $this->latestMessage->created_at?->toIso8601String(),
            ]),
            'unread_count' => $this->whenCounted('unread'),
            'read_receipts' => $this->when($this->isDirect() && $this->relationLoaded('participants'), fn () => $this->participants
                ->map(fn (User $user) => [
                    'user_id' => $user->id,
                    'last_read_message_id' => $user->getRelationValue('pivot')?->last_read_message_id,
                ])->values()->all()),
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
