<?php

namespace App\Actions\Profile;

use App\Interfaces\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Services\Auth\TokenService;

class ChangePasswordAction
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TokenService $tokens,
    ) {}

    /**
     * Changes the password and logs out every other device.
     * The device making the request stays logged in.
     */
    public function handle(User $user, string $newPassword): void
    {
        $this->users->update($user, ['password' => $newPassword]);

        $this->tokens->revokeOthers($user);

        activity('auth')->causedBy($user)->performedOn($user)->event('password_changed')->log('Password changed');
    }
}
