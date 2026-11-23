<?php

namespace App\Http\Requests\V1\Admin\User;

use App\Enums\AvailabilityStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', User::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'is_active' => ['nullable', 'boolean'],
            'availability_status' => ['nullable', Rule::enum(AvailabilityStatus::class)],
            'sort' => ['nullable', Rule::in(['name', '-name', 'email', '-email', 'created_at', '-created_at', 'last_seen_at', '-last_seen_at'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
