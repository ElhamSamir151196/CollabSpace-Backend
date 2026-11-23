<?php

namespace App\Actions\Admin;

use App\Exceptions\BusinessException;
use App\Interfaces\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Services\Auth\TokenService;

class ChangeUserStatusAction
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TokenService $tokens,
    ) {}

    /**
     * Deactivated users are logged out everywhere and can't log in again
     * until an admin re-activates them.
     */
    public function handle(User $actor, User $user, bool $isActive): User
    {
        if ($actor->is($user) && ! $isActive) {
            throw new BusinessException(__('You cannot deactivate your own account.'));
        }

        $user = $this->users->update($user, ['is_active' => $isActive]);

        if (! $isActive) {
            $this->tokens->revokeAll($user);
        }

        return $user;
    }
}
