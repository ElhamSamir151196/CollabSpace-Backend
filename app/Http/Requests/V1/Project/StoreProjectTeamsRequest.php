<?php

namespace App\Http\Requests\V1\Project;

use App\Http\Requests\Concerns\ValidatesProjectAssignments;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectTeamsRequest extends FormRequest
{
    use ValidatesProjectAssignments;

    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && (bool) $this->user()?->can('manageMembers', $project);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->teamIdsRules(required: true);
    }

    /**
     * @return list<int>
     */
    public function teamIds(): array
    {
        return array_map('intval', (array) $this->validated('team_ids'));
    }
}
