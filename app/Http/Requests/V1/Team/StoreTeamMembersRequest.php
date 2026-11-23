<?php

namespace App\Http\Requests\V1\Team;

use App\DTOs\Team\TeamMemberData;
use App\Http\Requests\Concerns\ValidatesTeamMembers;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTeamMembersRequest extends FormRequest
{
    use ValidatesTeamMembers;

    public function authorize(): bool
    {
        $team = $this->route('team');

        return $team instanceof Team && (bool) $this->user()?->can('manageMembers', $team);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->memberRules(required: true);
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return $this->singleLeaderCheck();
    }

    /**
     * @return list<TeamMemberData>
     */
    public function members(): array
    {
        /** @var list<array{user_id: int|string, position?: string|null, is_leader?: bool|null}> $rows */
        $rows = $this->validated('members');

        return TeamMemberData::collect($rows);
    }
}
