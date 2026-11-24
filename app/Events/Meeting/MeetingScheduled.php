<?php

namespace App\Events\Meeting;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A meeting was created. invitedIds = the participants to notify (not the creator).
 * Listened to by the Notifications module.
 */
class MeetingScheduled
{
    use Dispatchable, SerializesModels;

    /**
     * @param  list<int>  $invitedIds
     */
    public function __construct(
        public readonly Meeting $meeting,
        public readonly array $invitedIds,
        public readonly User $by,
    ) {}
}
