<?php

namespace App\Http\Resources\V1;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Activity
 */
class ActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'log' => $this->log_name,
            'event' => $this->event,
            'description' => $this->description,
            'causer' => $this->causer instanceof User ? new UserSummaryResource($this->causer) : null,
            'subject' => $this->subject_type === null ? null : [
                'type' => $this->subjectAlias(),
                'id' => $this->subject_id,
            ],
            'properties' => $this->properties,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
