<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\AuthResult;
use App\DTOs\Auth\LoginData;
use App\Exceptions\BusinessException;
use App\Interfaces\Repositories\UserRepositoryInterface;
use App\Services\Auth\TokenService;
use Illuminate\Support\Facades\Hash;

class LoginAction
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TokenService $tokens,
    ) {}

    public function handle(LoginData $data): AuthResult
    {
        $user = $this->users->findByEmail($data->email);

        // Same message whether the email or the password is wrong,
        // so the endpoint can't be used to discover registered emails.
        if ($user === null || ! $user->hasPassword() || ! Hash::check($data->password, (string) $user->password)) {
            activity('auth')
                ->event('login_failed')
                ->withProperties(['email' => $data->email, 'ip' => request()->ip()])
                ->log('Failed login attempt');

            throw BusinessException::unauthorized(__('These credentials do not match our records.'));
        }

        if (! $user->is_active) {
            throw BusinessException::forbidden(__('Your account has been deactivated.'));
        }

        activity('auth')->causedBy($user)->performedOn($user)->event('login')->log('Logged in');

        return new AuthResult($user, $this->tokens->issue($user, $data->remember, $data->deviceName));
    }
}
