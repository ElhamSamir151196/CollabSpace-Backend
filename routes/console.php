<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
|
| Run locally with:  php artisan schedule:work
| On a server, one cron entry: * * * * * php artisan schedule:run
|
*/

// Remove API tokens that expired more than 24 hours ago.
Schedule::command('sanctum:prune-expired --hours=24')->daily();

// "Design Review starts at 10:00" — 15 minutes before each meeting.
Schedule::command('meetings:send-reminders')->everyFiveMinutes()->withoutOverlapping();

// "Create Wireframes is due tomorrow" — every morning.
Schedule::command('tasks:send-due-reminders')->dailyAt('09:00')->withoutOverlapping();

// Read notifications older than 30 days.
Schedule::command('notifications:prune')->daily();
