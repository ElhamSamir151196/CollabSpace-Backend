<?php

namespace App\Http\Requests\V1\Meeting;

use App\Models\Meeting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Details only (title, description, link). Date / time: PATCH /meetings/{meeting}/reschedule.
 */
class UpdateMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('update', $this->meeting());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'meeting_link' => ['sometimes', 'nullable', 'url:http,https', 'max:500'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesToUpdate(): array
    {
        return $this->safe()->only(['title', 'description', 'meeting_link']);
    }

    public function meeting(): Meeting
    {
        /** @var Meeting */
        return $this->route('meeting');
    }
}
