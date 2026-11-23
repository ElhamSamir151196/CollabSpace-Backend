<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Traits\Filterable;
use App\Traits\HasAttachments;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $project_id
 * @property int $team_id
 * @property string $title
 * @property string|null $description
 * @property TaskStatus $status
 * @property Priority $priority
 * @property int $progress
 * @property Carbon|null $start_date
 * @property Carbon|null $due_date
 * @property int|null $reviewer_id
 * @property int $position
 * @property Carbon|null $completed_at
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Project $project
 * @property-read Team $team
 * @property-read User|null $reviewer
 * @property-read User|null $creator
 * @property-read Collection<int, User> $assignees
 * @property int|null $attachments_count
 */
#[Fillable([
    'project_id',
    'team_id',
    'title',
    'description',
    'status',
    'priority',
    'progress',
    'start_date',
    'due_date',
    'reviewer_id',
    'position',
    'completed_at',
    'created_by',
])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use Filterable, HasAttachments, HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'description' => null,
        'status' => 'pending',
        'priority' => 'medium',
        'progress' => 0,
        'start_date' => null,
        'due_date' => null,
        'reviewer_id' => null,
        'position' => 0,
        'completed_at' => null,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => Priority::class,
            'progress' => 'integer',
            'position' => 'integer',
            'start_date' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
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
     * Includes soft-deleted teams, so old tasks still show their team.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps()->orderBy('users.name');
    }

    /**
     * Tasks inside projects the user can see.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        $query->whereHas('project', fn (Builder $projects) => $projects->visibleTo($user));
    }

    /**
     * Uses the loaded assignees when available (no extra query on boards).
     */
    public function isAssignedTo(User $user): bool
    {
        return $this->relationLoaded('assignees')
            ? $this->assignees->contains('id', $user->id)
            : $this->assignees()->whereKey($user->id)->exists();
    }

    public function isReviewedBy(User $user): bool
    {
        return $this->reviewer_id === $user->id;
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && ! $this->due_date->isToday()
            && $this->status !== TaskStatus::Completed;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('tasks')
            ->logOnly(['title', 'status', 'priority', 'progress', 'team_id', 'reviewer_id', 'due_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
