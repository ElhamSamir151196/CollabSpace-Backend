<?php

namespace App\Http\Requests\V1\Meeting;

use App\Http\Requests\Concerns\ValidatesMeetingSchedule;
use App\Models\Meeting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RescheduleMeetingRequest extends FormRequest
{
    use ValidatesMeetingSchedule;

    public function authorize(): bool
    {
        /** @var Meeting $meeting */
        $meeting = $this->route('meeting');

        return (bool) $this->user()?->can('update', $meeting);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->scheduleRules();
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->ensureStartsInFuture($validator)];
    }
}
