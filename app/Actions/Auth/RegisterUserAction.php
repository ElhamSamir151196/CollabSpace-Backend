<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\AuthResult;
use App\DTOs\Auth\RegisterData;
use App\Enums\UserRole;
use App\Interfaces\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Services\Auth\TokenService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;

/**
 * Self sign-up. New accounts are always Team Members;
 * only an admin can promote someone to Project Manager or Admin.
 */
class RegisterUserAction
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TokenService $tokens,
    ) {}

    public function handle(RegisterData $data): AuthResult
    {
        $user = DB::transaction(function () use ($data): User {
            $user = $this->users->create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,
            ]);

            $user->assignRole(UserRole::TeamMember->value);

            return $user;
        });

        event(new Registered($user));

        activity('auth')->causedBy($user)->performedOn($user)->event('registered')->log('Registered');

        return new AuthResult($user, $this->tokens->issue($user, deviceName: $data->deviceName));
    }
}
