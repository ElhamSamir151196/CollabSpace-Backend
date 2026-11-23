<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

/**
 * Rules for "team_ids" and "guest_ids" arrays used when creating a project
 * and when assigning teams / guests later.
 */
trait ValidatesProjectAssignments
{
    /**
     * @return array<string, array<mixed>>
     */
    protected function teamIdsRules(bool $required): array
    {
        return [
            'team_ids' => [$required ? 'required' : 'sometimes', 'array', 'min:1', 'max:50'],
            'team_ids.*' => [
                'integer', 'distinct',
                Rule::exists('teams', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    /**
     * @return array<string, array<mixed>>
     */
    protected function guestIdsRules(bool $required): array
    {
        return [
            'guest_ids' => [$required ? 'required' : 'sometimes', 'array', 'min:1', 'max:100'],
            'guest_ids.*' => [
                'integer', 'distinct',
                Rule::exists('users', 'id')->where('is_active', true)->whereNull('deleted_at'),
            ],
        ];
    }
}
