<?php

namespace App\Http\Requests\V1\Meeting;

use App\Models\Meeting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UpdateMeetingCoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Meeting $meeting */
        $meeting = $this->route('meeting');

        return (bool) $this->user()?->can('manageContent', $meeting);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cover' => [
                'required',
                File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max((int) config('collabspace.meetings.cover_max_kb')),
            ],
        ];
    }
}
