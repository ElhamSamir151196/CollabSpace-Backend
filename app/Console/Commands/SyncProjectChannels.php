<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\Chat\ProjectChannels;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Creates the missing chat channels for projects that existed before
 * the chat module (or after a failed run). Safe to run many times.
 */
#[Signature('chat:sync-channels')]
#[Description('Create the missing chat channels (public + team) for every project')]
class SyncProjectChannels extends Command
{
    public function handle(ProjectChannels $channels): int
    {
        $count = 0;

        Project::query()->orderBy('id')->each(function (Project $project) use ($channels, &$count) {
            $channels->ensureFor($project);
            $count++;
        });

        $this->info("Channels checked for {$count} project(s).");

        return self::SUCCESS;
    }
}
