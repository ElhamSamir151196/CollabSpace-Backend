<?php

namespace App\Http\Requests\V1\Team;

use App\Http\Requests\Concerns\ValidatesTeamMembers;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamRequest extends FormRequest
{
    use ValidatesTeamMembers;

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Team::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('teams', 'name')->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'hex_color', 'size:7'],
            ...$this->memberRules(required: false),
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return $this->singleLeaderCheck();
    }
}
