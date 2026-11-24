<?php

namespace App\Models;

use App\Enums\MeetingNoteType;
use Database\Factories\MeetingNoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A note or a comment on a meeting.
 *
 * @property int $id
 * @property int $meeting_id
 * @property int|null $user_id
 * @property MeetingNoteType $type
 * @property string $body
 * @property Carbon|null $edited_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Meeting $meeting
 * @property-read User|null $author
 */
#[Fillable(['meeting_id', 'user_id', 'type', 'body', 'edited_at'])]
class MeetingNote extends Model
{
    /** @use HasFactory<MeetingNoteFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'edited_at' => null,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MeetingNoteType::class,
            'edited_at' => 'datetime',
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
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function isAuthoredBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }
}
