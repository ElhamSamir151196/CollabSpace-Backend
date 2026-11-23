<?php

namespace App\Http\Requests\Concerns;

/**
 * Lower-cases and trims "email" before validation, so
 * "Sara@Mail.com " and "sara@mail.com" are the same account.
 */
trait NormalizesEmail
{
    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        if (is_string($email)) {
            $this->merge(['email' => strtolower(trim($email))]);
        }
    }
}
