<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Password;

/**
 * Emails a reset link that points to the React app
 * (FRONTEND_URL/reset-password?token=...&email=...).
 * The URL is built in AppServiceProvider::configurePasswordResetUrl().
 */
class SendPasswordResetLinkAction
{
    public function handle(string $email): void
    {
        // The result is ignored on purpose: the API answers the same way
        // whether the email exists or not, so emails can't be discovered.
        Password::broker()->sendResetLink(['email' => $email]);
    }
}
