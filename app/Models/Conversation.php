<?php

namespace App\Models;

use App\Enums\ConversationType;
use App\Enums\Permission;
use Database\Factories\ConversationFactory;
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
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Who can open a conversation:
 *  - direct:  its two participants (admins can NOT read other people's direct chats);
 *  - project: every project member, and admins (chats.view-all);
 *  - team:    members of that team while it is assigned to the project,
 *             the project's manager, and admins.
 *
 * @property int $id
 * @property ConversationType $type
 * @property int|null $project_id
 * @property int|null $team_id
 * @property string|null $name
 * @property string|null $direct_key
 * @property int|null $created_by
 * @property Carbon|null $last_message_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $unread_count
 * @property-read Project|null $project
 * @property-read Team|null $team
 * @property-read Collection<int, User> $participants
 * @property-read Message|null $latestMessage
 */
#[Fillable(['type', 'project_id', 'team_id', 'name', 'direct_key', 'created_by', 'last_message_at'])]
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'project_id' => null,
        'team_id' => null,
        'name' => null,
        'direct_key' => null,
        'last_message_at' => null,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ConversationType::class,
            'last_message_at' => 'datetime',
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
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class)->withTrashed();
    }

    /**
     * Users with a state row (both people of a direct chat, and channel
     * members once they have read something).
     *
     * @return BelongsToMany<User, $this>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['last_read_message_id', 'last_read_at'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return HasOne<Message, $this>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public static function directKey(int $userA, int $userB): string
    {
        return min($userA, $userB).':'.max($userA, $userB);
    }

    public function isDirect(): bool
    {
        return $this->type === ConversationType::Direct;
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function accessibleBy(Builder $query, User $user): void
    {
        $viewAll = $user->can(Permission::ChatsViewAll->value);

        $query->where(function (Builder $query) use ($user, $viewAll) {
            $query->where(fn (Builder $direct) => $direct
                ->where('type', ConversationType::Direct->value)
                ->whereHas('participants', fn (Builder $users) => $users->whereKey($user->id)));

            $query->orWhere(function (Builder $channel) use ($user, $viewAll) {
                $channel->where('type', ConversationType::Project->value)
                    ->whereHas('project', fn (Builder $projects) => $viewAll ? $projects : $projects->visibleTo($user));
            });

            $query->orWhere(function (Builder $channel) use ($user, $viewAll) {
                $channel->where('type', ConversationType::Team->value)->whereHas('project');

                if ($viewAll) {
                    return;
                }

                $channel->where(function (Builder $access) use ($user) {
                    $access->whereHas('project', fn (Builder $projects) => $projects->where('manager_id', $user->id))
                        ->orWhere(fn (Builder $member) => $member
                            ->whereHas('team.members', fn (Builder $users) => $users->whereKey($user->id))
                            ->whereExists(fn (QueryBuilder $assigned) => $assigned
                                ->select(DB::raw(1))
                                ->from('project_team')
                                ->whereColumn('project_team.project_id', 'conversations.project_id')
                                ->whereColumn('project_team.team_id', 'conversations.team_id')));
                });
            });
        });
    }

    /**
     * Results of isAccessibleBy() per user id for this instance, so rendering
     * 30 messages of the same chat does not repeat the same queries.
     *
     * @var array<int, bool>
     */
    protected array $accessCache = [];

    public function isAccessibleBy(User $user): bool
    {
        return $this->accessCache[$user->id] ??= $this->resolveAccess($user);
    }

    private function resolveAccess(User $user): bool
    {
        if ($this->isDirect()) {
            return $this->participants()->whereKey($user->id)->exists();
        }

        $project = $this->project;

        if ($project === null) {
            return false;
        }

        if ($user->can(Permission::ChatsViewAll->value)) {
            return true;
        }

        if ($this->type === ConversationType::Project) {
            return $project->hasMember($user);
        }

        return $project->isManagedBy($user)
            || ($this->team_id !== null
                && $project->teams()->whereKey($this->team_id)->exists()
                && $this->team?->hasMember($user) === true);
    }
}
