<?php

namespace App\Http\Requests\V1\Meeting;

use App\Models\MeetingNote;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMeetingNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var MeetingNote $note */
        $note = $this->route('note');

        return (bool) $this->user()?->can('update', $note);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
