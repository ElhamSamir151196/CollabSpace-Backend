<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\ResetPasswordData;
use App\Models\User;
use App\Services\Auth\TokenService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResetPasswordAction
{
    public function __construct(private readonly TokenService $tokens) {}

    public function handle(ResetPasswordData $data): void
    {
        $status = Password::broker()->reset(
            ['email' => $data->email, 'token' => $data->token, 'password' => $data->password],
            function (User $user, string $password): void {
                $user->forceFill(['password' => $password])
                    ->setRememberToken(Str::random(60));
                $user->save();

                // Anyone who had the old password is logged out everywhere.
                $this->tokens->revokeAll($user);

                event(new PasswordReset($user));

                activity('auth')->causedBy($user)->performedOn($user)->event('password_reset')->log('Password reset');
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__(is_string($status) ? $status : 'passwords.token')],
            ]);
        }
    }
}
