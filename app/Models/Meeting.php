<?php

namespace App\Models;

use App\Enums\MeetingStatus;
use App\Traits\Filterable;
use Database\Factories\MeetingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A scheduled meeting of a project. The call itself happens outside the app
 * (meeting_link); the app keeps the schedule, participants, transcript,
 * notes, comments and (later) the AI summary.
 *
 * @property int $id
 * @property int $project_id
 * @property string $title
 * @property string|null $description
 * @property Carbon $start_at
 * @property Carbon $end_at
 * @property string|null $meeting_link
 * @property string|null $cover_path
 * @property MeetingStatus $status
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $reminder_sent_at
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $participants_count
 * @property int|null $transcripts_count
 * @property int|null $notes_count
 * @property-read Project $project
 * @property-read User|null $creator
 * @property-read Collection<int, User> $participants
 * @property-read Collection<int, MeetingTranscript> $transcripts
 * @property-read Collection<int, MeetingNote> $notes
 * @property-read MeetingSummary|null $summary
 */
#[Fillable([
    'project_id',
    'title',
    'description',
    'start_at',
    'end_at',
    'meeting_link',
    'cover_path',
    'status',
    'cancelled_at',
    'reminder_sent_at',
    'created_by',
])]
class Meeting extends Model
{
    /** @use HasFactory<MeetingFactory> */
    use Filterable, HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'description' => null,
        'meeting_link' => null,
        'cover_path' => null,
        'status' => 'scheduled',
        'cancelled_at' => null,
        'reminder_sent_at' => null,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'status' => MeetingStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany<User, $this, MeetingParticipant, 'attendance'>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(MeetingParticipant::class)
            ->as('attendance')
            ->withPivot(['attendance_status', 'responded_at', 'joined_at'])
            ->withTimestamps()
            ->orderBy('users.name');
    }

    /**
     * @return HasMany<MeetingTranscript, $this>
     */
    public function transcripts(): HasMany
    {
        return $this->hasMany(MeetingTranscript::class);
    }

    /**
     * @return HasMany<MeetingNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(MeetingNote::class);
    }

    /**
     * @return HasOne<MeetingSummary, $this>
     */
    public function summary(): HasOne
    {
        return $this->hasOne(MeetingSummary::class);
    }

    /**
     * Meetings of projects the user can see.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        $query->whereHas('project', fn (Builder $projects) => $projects->visibleTo($user));
    }

    public function isCreatedBy(User $user): bool
    {
        return $this->created_by === $user->id;
    }

    public function hasParticipant(User $user): bool
    {
        return $this->relationLoaded('participants')
            ? $this->participants->contains('id', $user->id)
            : $this->participants()->whereKey($user->id)->exists();
    }

    public function isCancelled(): bool
    {
        return $this->status === MeetingStatus::Cancelled;
    }

    /**
     * upcoming | live | ended | cancelled — computed from the time, not stored.
     */
    public function state(): string
    {
        return match (true) {
            $this->isCancelled() => 'cancelled',
            now()->lt($this->start_at) => 'upcoming',
            now()->lte($this->end_at) => 'live',
            default => 'ended',
        };
    }

    public function durationMinutes(): int
    {
        return (int) $this->start_at->diffInMinutes($this->end_at);
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path !== null
            ? Storage::disk((string) config('collabspace.meetings.cover_disk'))->url($this->cover_path)
            : null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('meetings')
            ->logOnly(['title', 'start_at', 'end_at', 'meeting_link', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
