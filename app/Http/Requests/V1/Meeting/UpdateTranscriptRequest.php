<?php

namespace App\Http\Requests\V1\Meeting;

use App\Models\Meeting;
use App\Rules\ProjectMember;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTranscriptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manageContent', $this->meeting());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'speaker_id' => ['sometimes', 'nullable', 'integer', new ProjectMember($this->meeting()->project)],
            'speaker_name' => ['sometimes', 'required', 'string', 'max:150'],
            'offset' => ['sometimes', 'required', 'regex:'.StoreTranscriptRequest::OFFSET_PATTERN],
            'content' => ['sometimes', 'required', 'string', 'max:5000'],
            'language' => ['sometimes', 'required', 'string', 'alpha_dash', 'max:10'],
        ];
    }

    public function meeting(): Meeting
    {
        /** @var Meeting */
        return $this->route('meeting');
    }
}
