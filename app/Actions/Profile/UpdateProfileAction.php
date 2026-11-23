<?php

namespace App\Actions\Profile;

use App\DTOs\Profile\UpdateProfileData;
use App\Interfaces\Repositories\UserRepositoryInterface;
use App\Models\User;

class UpdateProfileAction
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function handle(User $user, UpdateProfileData $data): User
    {
        if ($data->attributes === []) {
            return $user;
        }

        return $this->users->update($user, $data->attributes);
    }
}
