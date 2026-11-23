<?php

namespace App\DTOs\Auth;

use App\Models\User;

/**
 * What every successful sign-in returns: the user and (optionally) a new token.
 * GET /auth/me reuses it without a token.
 */
final readonly class AuthResult
{
    public function __construct(
        public User $user,
        public ?IssuedToken $token = null,
    ) {}
}
