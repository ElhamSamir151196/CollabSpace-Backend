<?php

namespace App\Http\Requests\V1\Admin;

use App\Enums\Permission;
use App\Models\Activity;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexActivityLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(Permission::ActivityLogView->value);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'log' => ['nullable', 'string', 'max:50'],
            'event' => ['nullable', 'string', 'max:50'],
            'causer_id' => ['nullable', 'integer'],
            'subject_type' => ['nullable', Rule::in(array_keys(Activity::SUBJECT_TYPES))],
            'subject_id' => ['nullable', 'integer', 'required_with:subject_type'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'sort' => ['nullable', Rule::in(['created_at', '-created_at'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
