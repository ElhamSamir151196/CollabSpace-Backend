<?php

namespace App\Actions\Meeting;

use App\Interfaces\Repositories\MeetingRepositoryInterface;
use App\Models\Meeting;

class DeleteMeetingAction
{
    public function __construct(private readonly MeetingRepositoryInterface $meetings) {}

    /**
     * Soft delete (transcript and notes stay in the database).
     */
    public function handle(Meeting $meeting): void
    {
        $this->meetings->delete($meeting);
    }
}
