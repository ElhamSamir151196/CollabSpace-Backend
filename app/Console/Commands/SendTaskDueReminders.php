<?php

namespace App\Console\Commands;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Notifications\TaskDueSoonNotification;
use App\Services\Notifications\Recipients;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Runs every morning (routes/console.php): tells the assignees of open
 * tasks that are due tomorrow. Each due date once.
 */
#[Signature('tasks:send-due-reminders')]
#[Description('Notify assignees of open tasks that are due tomorrow')]
class SendTaskDueReminders extends Command
{
    public function handle(Recipients $recipients): int
    {
        $count = 0;

        Task::query()
            ->with('project')
            ->whereDate('due_date', today()->addDay())
            ->where('status', '!=', TaskStatus::Completed->value)
            ->whereNull('due_reminder_sent_at')
            ->whereHas('project')
            ->each(function (Task $task) use ($recipients, &$count) {
                Notification::send(
                    $recipients->of($task->assignees()->pluck('users.id')),
                    new TaskDueSoonNotification($task),
                );

                $task->forceFill(['due_reminder_sent_at' => now()])->saveQuietly();
                $count++;
            });

        $this->info("Due reminders sent for {$count} task(s).");

        return self::SUCCESS;
    }
}
