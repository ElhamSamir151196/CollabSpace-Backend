<?php

namespace App\Http\Requests\Concerns;

use App\DTOs\Meeting\MeetingSchedule;
use Illuminate\Validation\Validator;

/**
 * The date / time part of the meeting form:
 *   date (Y-m-d), start_time (H:i) and end_time (H:i) OR duration_minutes.
 * The meeting must start in the future and last 5 minutes to 12 hours.
 */
trait ValidatesMeetingSchedule
{
    /**
     * @return array<string, array<mixed>>
     */
    protected function scheduleRules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required_without:duration_minutes', 'nullable', 'date_format:H:i', 'after:start_time'],
            'duration_minutes' => ['required_without:end_time', 'nullable', 'integer', 'min:5', 'max:720'],
        ];
    }

    protected function ensureStartsInFuture(Validator $validator): void
    {
        if ($validator->errors()->hasAny(['date', 'start_time', 'end_time', 'duration_minutes'])) {
            return;
        }

        if (MeetingSchedule::fromRequest($this)->startAt->isPast()) {
            $validator->errors()->add('start_time', __('The meeting must start in the future.'));
        }
    }
}
