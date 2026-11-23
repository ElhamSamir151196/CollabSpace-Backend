<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Shared rules for a "members" array:
 *   members: [{ user_id, position?, is_leader? }]
 * Only active, non-deleted users; no duplicates; at most one leader.
 */
trait ValidatesTeamMembers
{
    /**
     * @return array<string, array<mixed>>
     */
    protected function memberRules(bool $required): array
    {
        return [
            'members' => [$required ? 'required' : 'sometimes', 'array', 'min:1', 'max:100'],
            'members.*.user_id' => [
                'required', 'integer', 'distinct',
                Rule::exists('users', 'id')->where('is_active', true)->whereNull('deleted_at'),
            ],
            'members.*.position' => ['nullable', 'string', 'max:100'],
            'members.*.is_leader' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    protected function singleLeaderCheck(): array
    {
        return [
            function (Validator $validator): void {
                $members = $this->input('members');

                if (! is_array($members)) {
                    return;
                }

                $leaders = collect($members)
                    ->filter(fn ($member) => is_array($member) && filter_var($member['is_leader'] ?? false, FILTER_VALIDATE_BOOLEAN))
                    ->count();

                if ($leaders > 1) {
                    $validator->errors()->add('members', __('A team can only have one leader.'));
                }
            },
        ];
    }
}
