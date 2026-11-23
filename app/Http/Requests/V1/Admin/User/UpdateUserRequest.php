<?php

namespace App\Http\Requests\V1\Admin\User;

use App\Enums\AvailabilityStatus;
use App\Http\Requests\Concerns\NormalizesEmail;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Admin edits another user's details. Role and status have their own endpoints.
 */
class UpdateUserRequest extends FormRequest
{
    use NormalizesEmail;

    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && (bool) $this->user()?->can('update', $user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes', 'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user instanceof User ? $user->id : null),
            ],
            'password' => ['sometimes', 'required', 'string', Password::defaults()],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]{6,30}$/'],
            'job_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'years_of_experience' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:60'],
            'availability_status' => ['sometimes', 'required', Rule::enum(AvailabilityStatus::class)],
        ];
    }
}
