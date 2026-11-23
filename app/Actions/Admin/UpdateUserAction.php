<?php

namespace App\Actions\Admin;

use App\DTOs\Admin\UpdateUserData;
use App\Interfaces\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Services\Auth\TokenService;

class UpdateUserAction
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TokenService $tokens,
    ) {}

    public function handle(User $user, UpdateUserData $data): User
    {
        if ($data->attributes === []) {
            return $user;
        }

        $user = $this->users->update($user, $data->attributes);

        // A password set by the admin logs the user out of every device.
        if ($data->changesPassword()) {
            $this->tokens->revokeAll($user);
        }

        return $user;
    }
}
