<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Keeps the notifications table small: read notifications older than N days are removed.
 * Unread ones are never deleted.
 */
#[Signature('notifications:prune {--days=30}')]
#[Description('Delete read notifications older than the given number of days')]
class PruneReadNotifications extends Command
{
    public function handle(): int
    {
        $deleted = DatabaseNotification::query()
            ->whereNotNull('read_at')
            ->where('read_at', '<', now()->subDays((int) $this->option('days')))
            ->delete();

        $this->info("Deleted {$deleted} read notification(s).");

        return self::SUCCESS;
    }
}
