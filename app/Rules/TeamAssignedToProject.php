<?php

namespace App\Rules;

use App\Models\Project;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The team must be one of the teams assigned to the project.
 */
class TeamAssignedToProject implements ValidationRule
{
    public function __construct(private readonly Project $project) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value) || ! $this->project->teams()->whereKey((int) $value)->exists()) {
            $fail(__('The selected team is not assigned to this project.'));
        }
    }
}
