<?php

namespace App\Notifications;

use App\Enums\NotificationKind;
use App\Models\Team;
use App\Models\User;

class AddedToTeamNotification extends AppNotification
{
    public function __construct(
        public readonly Team $team,
        public readonly ?User $by = null,
    ) {
        parent::__construct();
    }

    public function kind(): NotificationKind
    {
        return NotificationKind::AddedToTeam;
    }

    protected function message(): string
    {
        return __('You were added to the :team team.', ['team' => $this->team->name]);
    }

    protected function target(): array
    {
        return ['type' => 'team', 'id' => $this->team->id];
    }

    protected function actor(): ?User
    {
        return $this->by;
    }
}
