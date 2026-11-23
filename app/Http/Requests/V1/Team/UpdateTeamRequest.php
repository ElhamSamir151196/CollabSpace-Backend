<?php

namespace App\Http\Requests\V1\Team;

use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Team details only. Members are managed through /teams/{team}/members.
 */
class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->route('team');

        return $team instanceof Team && (bool) $this->user()?->can('update', $team);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $team = $this->route('team');

        return [
            'name' => [
                'sometimes', 'required', 'string', 'max:100',
                Rule::unique('teams', 'name')
                    ->ignore($team instanceof Team ? $team->id : null)
                    ->whereNull('deleted_at'),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'color' => ['sometimes', 'nullable', 'string', 'hex_color', 'size:7'],
        ];
    }
}
