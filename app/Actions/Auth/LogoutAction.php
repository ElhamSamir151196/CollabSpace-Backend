<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Auth\TokenService;

class LogoutAction
{
    public function __construct(private readonly TokenService $tokens) {}

    /**
     * Revokes only the token used for this request (this device).
     */
    public function handle(User $user): void
    {
        $this->tokens->revokeCurrent($user);

        activity('auth')->causedBy($user)->performedOn($user)->event('logout')->log('Logged out');
    }
}
