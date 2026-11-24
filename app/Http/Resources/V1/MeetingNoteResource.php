<?php

namespace App\Http\Resources\V1;

use App\Models\MeetingNote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MeetingNote
 */
class MeetingNoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'type' => $this->type->toArray(),
            'body' => $this->body,
            'author' => new UserSummaryResource($this->whenLoaded('author')),
            'is_edited' => $this->edited_at !== null,
            'can' => $this->when($user !== null, fn () => [
                'update' => (bool) $user?->can('update', $this->resource),
                'delete' => (bool) $user?->can('delete', $this->resource),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
