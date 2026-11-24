<?php

namespace App\Actions\Meeting;

use App\Interfaces\Repositories\MeetingRepositoryInterface;
use App\Models\Meeting;

/**
 * Edits the details (title, description, link).
 * Date and time changes go through RescheduleMeetingAction.
 */
class UpdateMeetingAction
{
    public function __construct(private readonly MeetingRepositoryInterface $meetings) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Meeting $meeting, array $attributes): Meeting
    {
        if ($attributes === []) {
            return $meeting;
        }

        return $this->meetings->update($meeting, $attributes);
    }
}
