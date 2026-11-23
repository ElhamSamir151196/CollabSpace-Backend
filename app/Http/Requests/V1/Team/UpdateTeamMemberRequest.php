<?php

namespace App\Http\Requests\V1\Team;

use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamMemberRequest extends FormRequest
{
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
        return [
            'position' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_leader' => ['sometimes', 'boolean'],
        ];
    }
}
