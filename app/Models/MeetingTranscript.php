<?php

namespace App\Models;

use Database\Factories\MeetingTranscriptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $meeting_id
 * @property int|null $speaker_id
 * @property string $speaker_name
 * @property int $offset_seconds
 * @property string $content
 * @property string $language
 * @property int $position
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Meeting $meeting
 * @property-read User|null $speaker
 */
#[Fillable(['meeting_id', 'speaker_id', 'speaker_name', 'offset_seconds', 'content', 'language', 'position', 'created_by'])]
class MeetingTranscript extends Model
{
    /** @use HasFactory<MeetingTranscriptFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'offset_seconds' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Meeting, $this>
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function speaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'speaker_id')->withTrashed();
    }

    /**
     * 40 -> "00:40", 3725 -> "01:02:05".
     */
    public function timestamp(): string
    {
        $hours = intdiv($this->offset_seconds, 3600);
        $minutes = intdiv($this->offset_seconds % 3600, 60);
        $seconds = $this->offset_seconds % 60;

        return $hours > 0
            ? sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds)
            : sprintf('%02d:%02d', $minutes, $seconds);
    }
}
