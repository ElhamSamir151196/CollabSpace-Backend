<?php

namespace App\Http\Resources\V1;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A chat message. "can" is only included in HTTP responses
 * (not in real-time events, which are sent to many users).
 *
 * @mixin Message
 */
class MessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'parent_id' => $this->parent_id,
            'body' => $this->body,
            'author' => new UserSummaryResource($this->whenLoaded('author')),
            'mentions' => UserSummaryResource::collection($this->whenLoaded('mentions')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'replies_count' => $this->whenCounted('replies'),
            'is_edited' => $this->edited_at !== null,
            'edited_at' => $this->edited_at?->toIso8601String(),
            'can' => $this->when($user !== null, fn () => [
                'update' => (bool) $user?->can('update', $this->resource),
                'delete' => (bool) $user?->can('delete', $this->resource),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
