<?php

namespace App\Console\Commands;

use App\Enums\AttendanceStatus;
use App\Enums\MeetingStatus;
use App\Enums\NotificationKind;
use App\Models\Meeting;
use App\Notifications\MeetingNotification;
use App\Services\Notifications\Recipients;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Runs every 5 minutes (routes/console.php): reminds the participants
 * of meetings starting in the next 15 minutes. Each meeting once.
 */
#[Signature('meetings:send-reminders {--minutes=15 : How long before the start}')]
#[Description('Notify participants of meetings that start soon')]
class SendMeetingReminders extends Command
{
    public function handle(Recipients $recipients): int
    {
        $meetings = Meeting::query()
            ->where('status', MeetingStatus::Scheduled->value)
            ->whereNull('reminder_sent_at')
            ->whereBetween('start_at', [now(), now()->addMinutes((int) $this->option('minutes'))])
            ->whereHas('project')
            ->get();

        foreach ($meetings as $meeting) {
            $ids = $meeting->participants()
                ->wherePivot('attendance_status', '!=', AttendanceStatus::Declined->value)
                ->pluck('users.id');

            Notification::send($recipients->of($ids), new MeetingNotification($meeting, NotificationKind::MeetingReminder));

            $meeting->forceFill(['reminder_sent_at' => now()])->saveQuietly();
        }

        $this->info("Reminders sent for {$meetings->count()} meeting(s).");

        return self::SUCCESS;
    }
}
