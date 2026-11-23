<?php

namespace App\Actions\Admin;

use App\DTOs\Admin\CreateUserData;
use App\Interfaces\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateUserAction
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function handle(CreateUserData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = $this->users->create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,
                'phone' => $data->phone,
                'job_title' => $data->jobTitle,
                'years_of_experience' => $data->yearsOfExperience,
                'email_verified_at' => now(),
            ]);

            $user->syncRoles([$data->role->value]);

            return $user;
        });
    }
}
