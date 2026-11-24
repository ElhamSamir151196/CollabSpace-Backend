<?php

namespace App\DTOs\Meeting;

use App\Http\Requests\V1\Meeting\StoreMeetingRequest;
use Illuminate\Http\UploadedFile;

final readonly class CreateMeetingData
{
    /**
     * @param  list<int>  $participantIds
     */
    public function __construct(
        public string $title,
        public ?string $description,
        public MeetingSchedule $schedule,
        public ?string $meetingLink,
        public array $participantIds = [],
        public ?UploadedFile $cover = null,
    ) {}

    public static function fromRequest(StoreMeetingRequest $request): self
    {
        $cover = $request->file('cover');

        return new self(
            title: $request->string('title')->trim()->toString(),
            description: $request->filled('description') ? $request->string('description')->trim()->toString() : null,
            schedule: MeetingSchedule::fromRequest($request),
            meetingLink: $request->filled('meeting_link') ? $request->string('meeting_link')->trim()->toString() : null,
            participantIds: $request->participantIds(),
            cover: $cover instanceof UploadedFile ? $cover : null,
        );
    }
}
