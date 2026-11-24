<?php

namespace App\Events\Meeting;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Listened to by the Notifications module.
 */
class MeetingCancelled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Meeting $meeting,
        public readonly User $by,
    ) {}
}
