<?php

namespace App\Http\Requests\V1\Meeting;

use App\Models\Meeting;
use App\Rules\ProjectMember;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Invite more people: any member of the project (from any of its teams).
 */
class StoreMeetingParticipantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manageParticipants', $this->meeting());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1', 'max:100'],
            'user_ids.*' => ['integer', 'distinct', new ProjectMember($this->meeting()->project)],
        ];
    }

    /**
     * @return list<int>
     */
    public function userIds(): array
    {
        return array_values(array_unique(array_map('intval', (array) $this->validated('user_ids'))));
    }

    public function meeting(): Meeting
    {
        /** @var Meeting */
        return $this->route('meeting');
    }
}
