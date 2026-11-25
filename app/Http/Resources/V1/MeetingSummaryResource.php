<?php

namespace App\Http\Resources\V1;

use App\Models\MeetingSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MeetingSummary
 */
class MeetingSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'meeting_id' => $this->meeting_id,
            'summary' => $this->summary,
            'key_points' => $this->key_points,
            'decisions' => $this->decisions,
            'action_items' => $this->action_items,
            'risks' => $this->risks,
            'next_steps' => $this->next_steps,
            'model' => $this->model,
            'generated_by' => new UserSummaryResource($this->whenLoaded('generator')),
            'generated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
