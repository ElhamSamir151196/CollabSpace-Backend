<?php

namespace App\Http\Requests\V1\Team;

use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTeamsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', Team::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'member_id' => ['nullable', 'integer'],
            'mine' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['name', '-name', 'created_at', '-created_at', 'members_count', '-members_count'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Filters for TeamFilter. "mine=1" becomes member_id = the current user.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        $filters = $this->safe()->except(['mine', 'per_page']);

        if ($this->boolean('mine')) {
            $filters['member_id'] = $this->user()?->id;
        }

        return $filters;
    }
}
