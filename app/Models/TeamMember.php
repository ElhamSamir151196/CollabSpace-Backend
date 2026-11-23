<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * A user's membership in a team (the team_user row).
 * Available as $user->membership when loaded through a team.
 *
 * @property int $id
 * @property int $team_id
 * @property int $user_id
 * @property string|null $position
 * @property bool $is_leader
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TeamMember extends Pivot
{
    protected $table = 'team_user';

    public $incrementing = true;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_leader' => 'boolean',
        ];
    }
}
