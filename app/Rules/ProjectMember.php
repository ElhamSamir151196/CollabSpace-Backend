<?php

namespace App\Rules;

use App\Models\Project;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The user must be active and belong to the project
 * (manager, member of one of its teams, or guest).
 */
class ProjectMember implements ValidationRule
{
    public function __construct(private readonly Project $project) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = is_numeric($value)
            ? User::query()->whereKey((int) $value)->where('is_active', true)->first()
            : null;

        if ($user === null || ! $this->project->hasMember($user)) {
            $fail(__('The selected user is not a member of this project.'));
        }
    }
}
