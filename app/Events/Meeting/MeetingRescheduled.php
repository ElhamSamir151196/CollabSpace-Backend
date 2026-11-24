<?php

namespace App\Events\Meeting;

use App\Models\Meeting;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * The meeting moved to another date / time.
 * Listened to by the Notifications module.
 */
class MeetingRescheduled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Meeting $meeting,
        public readonly CarbonInterface $previousStartAt,
        public readonly User $by,
    ) {}
}
