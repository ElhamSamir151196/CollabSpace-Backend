<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\AuthResult;
use App\DTOs\Auth\GoogleLoginData;
use App\Enums\UserRole;
use App\Exceptions\BusinessException;
use App\Interfaces\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Services\Auth\TokenService;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as GoogleUser;
use LogicException;
use Throwable;

/**
 * Sign in / sign up with Google.
 *
 *  1. Ask Google who owns the access token.
 *  2. Match by google_id, then by email (links Google to an existing account).
 *  3. Otherwise create a new Team Member account without a password.
 */
class LoginWithGoogleAction
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TokenService $tokens,
    ) {}

    public function handle(GoogleLoginData $data): AuthResult
    {
        $googleUser = $this->fetchGoogleUser($data->accessToken);

        $email = strtolower(trim((string) $googleUser->getEmail()));

        if ($email === '') {
            throw BusinessException::unauthorized(__('Your Google account has no email address.'));
        }

        $user = DB::transaction(fn (): User => $this->findOrCreateUser($googleUser, $email));

        if (! $user->is_active) {
            throw BusinessException::forbidden(__('Your account has been deactivated.'));
        }

        activity('auth')->causedBy($user)->performedOn($user)->event('login')
            ->withProperties(['provider' => 'google'])
            ->log('Logged in with Google');

        return new AuthResult($user, $this->tokens->issue($user, $data->remember, $data->deviceName));
    }

    private function fetchGoogleUser(string $accessToken): GoogleUser
    {
        $driver = Socialite::driver('google');

        if (! $driver instanceof GoogleProvider) {
            throw new LogicException('The "google" Socialite driver is not configured.');
        }

        try {
            return $driver->stateless()->userFromToken($accessToken);
        } catch (Throwable) {
            throw BusinessException::unauthorized(__('Invalid or expired Google token.'));
        }
    }

    private function findOrCreateUser(GoogleUser $googleUser, string $email): User
    {
        $googleId = (string) $googleUser->getId();

        $user = $this->users->findByGoogleId($googleId);

        if ($user !== null) {
            return $user;
        }

        $user = $this->users->findByEmail($email);

        if ($user !== null) {
            $this->users->update($user, [
                'google_id' => $googleId,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);

            return $user;
        }

        $user = $this->users->create([
            'name' => $googleUser->getName() ?: $email,
            'email' => $email,
            'google_id' => $googleId,
            'email_verified_at' => now(),
        ]);

        $user->assignRole(UserRole::TeamMember->value);

        activity('auth')->causedBy($user)->performedOn($user)->event('registered')
            ->withProperties(['provider' => 'google'])
            ->log('Registered with Google');

        return $user;
    }
}
