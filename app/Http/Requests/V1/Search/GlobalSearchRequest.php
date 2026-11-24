<?php

namespace App\Http\Requests\V1\Search;

use App\Services\Search\GlobalSearch;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ?q=design  &types=projects,tasks  &limit=5  &save=0
 */
class GlobalSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        // Accept "types=projects,tasks" as well as types[]=projects&types[]=tasks.
        $types = $this->input('types');

        if (is_string($types)) {
            $this->merge(['types' => array_values(array_filter(array_map('trim', explode(',', $types))))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'types' => ['nullable', 'array'],
            'types.*' => ['string', Rule::in(app(GlobalSearch::class)->sections())],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
            'save' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return list<string>|null
     */
    public function types(): ?array
    {
        /** @var list<string>|null $types */
        $types = $this->validated('types');

        return $types === null || $types === [] ? null : $types;
    }
}
