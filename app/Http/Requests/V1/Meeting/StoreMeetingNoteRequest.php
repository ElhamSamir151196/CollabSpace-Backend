<?php

namespace App\Http\Requests\V1\Meeting;

use App\Enums\MeetingNoteType;
use App\Models\Meeting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMeetingNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Meeting $meeting */
        $meeting = $this->route('meeting');

        return (bool) $this->user()?->can('addNote', $meeting);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(MeetingNoteType::class)],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
