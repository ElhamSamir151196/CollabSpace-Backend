<?php

namespace App\Actions\Admin;

use App\Exceptions\BusinessException;
use App\Interfaces\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Services\Auth\TokenService;

class DeleteUserAction
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TokenService $tokens,
    ) {}

    /**
     * Soft delete: the user disappears from the app but their history
     * (tasks, messages, files) keeps pointing to a real record.
     */
    public function handle(User $actor, User $user): void
    {
        if ($actor->is($user)) {
            throw new BusinessException(__('You cannot delete your own account.'));
        }

        $this->tokens->revokeAll($user);

        $this->users->delete($user);
    }
}
