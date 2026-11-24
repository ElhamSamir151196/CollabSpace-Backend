<?php

namespace App\DTOs\Meeting;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

/**
 * When the meeting happens, built from the form fields:
 *   date (Y-m-d) + start_time (H:i) + end_time (H:i) OR duration_minutes.
 * Times are read in the app timezone (APP_TIMEZONE).
 */
final readonly class MeetingSchedule
{
    public function __construct(
        public CarbonImmutable $startAt,
        public CarbonImmutable $endAt,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        $start = CarbonImmutable::parse($request->string('date')->toString().' '.$request->string('start_time')->toString());

        $end = $request->filled('end_time')
            ? CarbonImmutable::parse($request->string('date')->toString().' '.$request->string('end_time')->toString())
            : $start->addMinutes($request->integer('duration_minutes'));

        return new self($start, $end);
    }
}
