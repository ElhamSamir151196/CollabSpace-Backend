<?php

namespace App\Interfaces\Repositories;

use App\Models\User;

/**
 * @extends BaseRepositoryInterface<User>
 */
interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function findByGoogleId(string $googleId): ?User;
}
