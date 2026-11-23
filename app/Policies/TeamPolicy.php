<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Team;
use App\Models\User;

/**
 * - Everyone signed in can see teams and their members.
 * - Creating, editing and deleting teams: admins (teams.manage).
 * - Adding / editing / removing members: admins and project managers (teams.assign).
 */
class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Team $team): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::TeamsManage->value);
    }

    public function update(User $user, Team $team): bool
    {
        return $user->can(Permission::TeamsManage->value);
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->can(Permission::TeamsManage->value);
    }

    public function manageMembers(User $user, Team $team): bool
    {
        return $user->canAny([Permission::TeamsManage->value, Permission::TeamsAssign->value]);
    }
}
