<?php

namespace App\Services\Auth;

use App\DTOs\Auth\IssuedToken;
use App\Models\User;

/**
 * Creates Sanctum API tokens with an expiry date.
 * "Remember me" gets a longer lifetime (see config/collabspace.php).
 */
class TokenService
{
    public function issue(User $user, bool $remember = false, ?string $deviceName = null): IssuedToken
    {
        $expiresAt = $remember
            ? now()->addDays((int) config('collabspace.auth.remember_ttl_days'))
            : now()->addHours((int) config('collabspace.auth.token_ttl_hours'));

        $token = $user->createToken($deviceName ?: 'web', ['*'], $expiresAt);

        return new IssuedToken($token->plainTextToken, $expiresAt);
    }

    /**
     * Only called behind auth:sanctum, so a current token always exists.
     */
    public function revokeCurrent(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function revokeAll(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * Logs the user out everywhere except the device making the request.
     */
    public function revokeOthers(User $user): void
    {
        $user->tokens()
            ->whereKeyNot($user->currentAccessToken()->getKey())
            ->delete();
    }
}
