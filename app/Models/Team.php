<?php

namespace App\Models;

use App\Traits\Filterable;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
 * A company-wide team (UI/UX, Frontend, Backend, QA ...).
 * Teams are assigned to projects in the Projects module.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $color
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $members_count
 * @property-read Collection<int, User> $members
 * @property-read Collection<int, User> $leaders
 * @property-read User|null $creator
 */
#[Fillable(['name', 'description', 'color', 'created_by'])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use Filterable, HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'description' => null,
        'color' => null,
    ];

    /**
     * Leaders first, then by name.
     *
     * @return BelongsToMany<User, $this, TeamMember, 'membership'>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(TeamMember::class)
            ->as('membership')
            ->withPivot(['id', 'position', 'is_leader'])
            ->withTimestamps()
            ->orderByPivot('is_leader', 'desc')
            ->orderBy('users.name');
    }

    /**
     * Only the leader(s) — cheap to eager load on team lists.
     *
     * @return BelongsToMany<User, $this, TeamMember, 'membership'>
     */
    public function leaders(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(TeamMember::class)
            ->as('membership')
            ->withPivot(['id', 'position', 'is_leader'])
            ->withTimestamps()
            ->wherePivot('is_leader', true);
    }

    /**
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withTimestamps();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->whereKey($user->getKey())->exists();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('teams')
            ->logOnly(['name', 'description', 'color'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
