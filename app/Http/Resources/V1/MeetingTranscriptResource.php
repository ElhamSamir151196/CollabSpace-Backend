<?php

namespace App\Http\Resources\V1;

use App\Models\MeetingTranscript;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One line of the "Meeting Transcripts" panel.
 *
 * @mixin MeetingTranscript
 */
class MeetingTranscriptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'timestamp' => $this->timestamp(),
            'offset_seconds' => $this->offset_seconds,
            'speaker' => [
                'id' => $this->speaker_id,
                'name' => $this->speaker_name,
                'avatar_url' => $this->whenLoaded('speaker', fn () => $this->speaker?->avatarUrl()),
            ],
            'content' => $this->content,
            'language' => $this->language,
        ];
    }
}
