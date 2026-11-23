<?php

namespace App\Rules;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The user must be active and be a Project Manager or an Admin.
 */
class EligibleProjectManager implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = User::query()->whereKey((int) $value)->where('is_active', true)->first();

        if ($user === null || ! $user->hasAnyRole([UserRole::ProjectManager->value, UserRole::Admin->value])) {
            $fail(__('The selected manager must be an active project manager or admin.'));
        }
    }
}
