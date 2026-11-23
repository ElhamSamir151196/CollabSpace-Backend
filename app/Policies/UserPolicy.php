<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

/**
 * Admin user management. Only users with "users.manage" (admins) pass.
 * Rules like "you can't deactivate yourself" live in the Actions.
 */
class UserPolicy
{
    public function manage(User $actor): bool
    {
        return $actor->can(Permission::UsersManage->value);
    }

    public function viewAny(User $actor): bool
    {
        return $this->manage($actor);
    }

    public function create(User $actor): bool
    {
        return $this->manage($actor);
    }

    public function update(User $actor, User $user): bool
    {
        return $this->manage($actor);
    }

    public function delete(User $actor, User $user): bool
    {
        return $this->manage($actor);
    }
}
