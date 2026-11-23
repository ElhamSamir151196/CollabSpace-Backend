<?php

namespace App\Models;

use App\Enums\AvailabilityStatus;
use App\Enums\UserRole;
use App\Traits\Filterable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $password
 * @property string|null $google_id
 * @property string|null $avatar_path
 * @property string|null $phone
 * @property string|null $job_title
 * @property int|null $years_of_experience
 * @property AvailabilityStatus|null $availability_status
 * @property bool $is_active
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $last_seen_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Role> $roles
 * @property-read Collection<int, Team> $teams
 * @property-read TeamMember $membership Pivot data when loaded through Team::members()
 */
#[Fillable([
    'name',
    'email',
    'password',
    'google_id',
    'avatar_path',
    'phone',
    'job_title',
    'years_of_experience',
    'availability_status',
    'is_active',
    'last_seen_at',
    'email_verified_at',
])]
#[Hidden(['password', 'remember_token', 'google_id'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Filterable, HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable, SoftDeletes;

    /**
     * In-memory defaults that match the database defaults, so a user that was
     * just created (not re-fetched) already has is_active = true, etc.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'google_id' => null,
        'avatar_path' => null,
        'phone' => null,
        'job_title' => null,
        'years_of_experience' => null,
        'availability_status' => 'available',
        'is_active' => true,
        'last_seen_at' => null,
        'email_verified_at' => null,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'years_of_experience' => 'integer',
            'availability_status' => AvailabilityStatus::class,
        ];
    }

    /**
     * The single role of the user (the system gives every user exactly one).
     */
    public function role(): ?UserRole
    {
        $name = $this->roles->first()?->name;

        return $name !== null ? UserRole::tryFrom($name) : null;
    }

    /**
     * @return BelongsToMany<Team, $this, TeamMember, 'membership'>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->using(TeamMember::class)
            ->as('membership')
            ->withPivot(['id', 'position', 'is_leader'])
            ->withTimestamps();
    }

    /**
     * Projects this user is the Project Manager of.
     *
     * @return HasMany<Project, $this>
     */
    public function managedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'manager_id');
    }

    public function hasPassword(): bool
    {
        return $this->password !== null;
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path !== null
            ? Storage::disk('public')->url($this->avatar_path)
            : null;
    }

    /**
     * Audit log: record changes to these fields (never the password).
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('users')
            ->logOnly(['name', 'email', 'job_title', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
