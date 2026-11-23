<?php

namespace App\Actions\Admin;

use App\Enums\UserRole;
use App\Exceptions\BusinessException;
use App\Models\User;

class ChangeUserRoleAction
{
    public function handle(User $actor, User $user, UserRole $role): User
    {
        // Stops an admin from locking themselves (and maybe everyone) out.
        if ($actor->is($user) && $role !== UserRole::Admin) {
            throw new BusinessException(__('You cannot remove your own admin role.'));
        }

        $old = $user->role();

        $user->syncRoles([$role->value]);

        activity('users')->causedBy($actor)->performedOn($user)->event('role_changed')
            ->withProperties(['old' => $old?->value, 'new' => $role->value])
            ->log('Role changed');

        return $user->load('roles');
    }
}
