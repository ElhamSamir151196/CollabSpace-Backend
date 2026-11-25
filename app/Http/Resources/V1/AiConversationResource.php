<?php

namespace App\Http\Resources\V1;

use App\Models\AiConversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * List: title + preview of the last message. Details: + all messages.
 *
 * @mixin AiConversation
 */
class AiConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'last_message' => $this->whenLoaded('latestMessage', fn () => $this->latestMessage === null ? null : [
                'role' => $this->latestMessage->role->value,
                'content' => Str::limit($this->latestMessage->content, 100),
            ]),
            'messages_count' => $this->whenCounted('messages'),
            'messages' => AiMessageResource::collection($this->whenLoaded('messages')),
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
