<?php

namespace App\Listeners;

use App\Events\ProjectCreated;
use App\Events\ProjectTeamsAssigned;
use App\Services\Chat\ProjectChannels;

/**
 * Creates the chat channels of a project as soon as it exists,
 * and a new team channel whenever a team is assigned.
 * Runs synchronously so the channels are there in the same response.
 */
class CreateProjectChannels
{
    public function __construct(private readonly ProjectChannels $channels) {}

    public function handle(ProjectCreated|ProjectTeamsAssigned $event): void
    {
        $this->channels->ensureFor($event->project);
    }
}
