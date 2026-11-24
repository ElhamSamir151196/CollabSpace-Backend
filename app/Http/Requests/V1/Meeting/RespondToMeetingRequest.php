<?php

namespace App\Http\Requests\V1\Meeting;

use App\Enums\AttendanceStatus;
use App\Models\Meeting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RespondToMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Meeting $meeting */
        $meeting = $this->route('meeting');

        return (bool) $this->user()?->can('respond', $meeting);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([AttendanceStatus::Accepted->value, AttendanceStatus::Declined->value])],
        ];
    }
}
