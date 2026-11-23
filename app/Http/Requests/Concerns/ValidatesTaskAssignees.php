<?php

namespace App\Http\Requests\Concerns;

use App\Models\TeamMember;
use Illuminate\Validation\Validator;

/**
 * Assignees of a task must be active members of the task's team.
 */
trait ValidatesTaskAssignees
{
    /**
     * @return array<string, array<mixed>>
     */
    protected function assigneeRules(): array
    {
        return [
            'assignee_ids' => ['sometimes', 'array', 'max:50'],
            'assignee_ids.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return list<int>
     */
    public function assigneeIds(): array
    {
        return array_values(array_unique(array_map('intval', (array) $this->validated('assignee_ids', []))));
    }

    /**
     * @param  list<int>  $userIds
     */
    protected function ensureAssigneesInTeam(Validator $validator, int $teamId, array $userIds): void
    {
        if ($userIds === []) {
            return;
        }

        $inTeam = TeamMember::query()
            ->where('team_id', $teamId)
            ->whereIn('user_id', $userIds)
            ->whereHas('user', fn ($users) => $users->where('is_active', true))
            ->count();

        if ($inTeam !== count(array_unique($userIds))) {
            $validator->errors()->add('assignee_ids', __('All assignees must be active members of the selected team.'));
        }
    }
}
