<?php

namespace App\Models;

use App\Enums\Permission;
use App\Enums\Priority;
use App\Enums\ProjectStatus;
use App\Traits\Filterable;
use Database\Factories\ProjectFactory;
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
 * Who is in a project?
 *  - the manager (a Project Manager),
 *  - every member of the teams assigned to it,
 *  - guests added directly.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $type
 * @property ProjectStatus $status
 * @property Priority $priority
 * @property Carbon $start_date
 * @property Carbon $deadline
 * @property Carbon|null $completed_at
 * @property int $manager_id
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $teams_count
 * @property int|null $guests_count
 * @property-read User $manager
 * @property-read User|null $creator
 * @property-read Collection<int, Team> $teams
 * @property-read Collection<int, User> $guests
 */
#[Fillable([
    'name',
    'slug',
    'description',
    'type',
    'status',
    'priority',
    'start_date',
    'deadline',
    'completed_at',
    'manager_id',
    'created_by',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use Filterable, HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'description' => null,
        'type' => null,
        'status' => 'pending',
        'priority' => 'medium',
        'completed_at' => null,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'priority' => Priority::class,
            'start_date' => 'date',
            'deadline' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withTimestamps()->orderBy('teams.name');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function guests(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('added_by')
            ->withTimestamps()
            ->orderBy('users.name');
    }

    /**
     * Projects the user may see: everything for admins (projects.manage-all),
     * otherwise only projects they manage, are a guest of, or whose team they are in.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if ($user->can(Permission::ProjectsManageAll->value)) {
            return;
        }

        $query->where(function (Builder $query) use ($user) {
            $query->where('manager_id', $user->id)
                ->orWhereHas('guests', fn (Builder $guests) => $guests->whereKey($user->id))
                ->orWhereHas('teams.members', fn (Builder $members) => $members->whereKey($user->id));
        });
    }

    public function isManagedBy(User $user): bool
    {
        return $this->manager_id === $user->id;
    }

    public function hasMember(User $user): bool
    {
        return $this->isManagedBy($user)
            || $this->guests()->whereKey($user->id)->exists()
            || $this->teams()->whereHas('members', fn (Builder $members) => $members->whereKey($user->id))->exists();
    }

    /**
     * Past the deadline and still open.
     */
    public function isOverdue(): bool
    {
        return $this->deadline->isPast()
            && ! $this->deadline->isToday()
            && ! in_array($this->status, [ProjectStatus::Completed, ProjectStatus::Cancelled], true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('projects')
            ->logOnly(['name', 'status', 'priority', 'start_date', 'deadline', 'manager_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
