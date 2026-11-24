<?php

namespace App\Filters;

use App\Enums\MeetingStatus;
use Illuminate\Support\Carbon;

/**
 * ?from= ?to= (meetings overlapping the range) ?upcoming=1 ?project_id=
 * ?participant_id= ?status= ?search=
 */
class MeetingFilter extends QueryFilter
{
    protected array $sortable = ['start_at', 'created_at'];

    protected string $defaultSort = 'start_at';

    public function from(string $date): void
    {
        $this->builder->where('end_at', '>=', Carbon::parse($date)->startOfDay());
    }

    public function to(string $date): void
    {
        $this->builder->where('start_at', '<=', Carbon::parse($date)->endOfDay());
    }

    public function upcoming(bool|int|string $upcoming): void
    {
        if (! filter_var($upcoming, FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        // Not finished yet (includes meetings happening right now).
        $this->builder
            ->where('end_at', '>=', now())
            ->where('status', '!=', MeetingStatus::Cancelled->value);
    }

    public function projectId(int|string $projectId): void
    {
        $this->builder->where('project_id', (int) $projectId);
    }

    public function participantId(int|string $userId): void
    {
        $this->builder->whereHas('participants', fn ($query) => $query->whereKey((int) $userId));
    }

    public function status(string $status): void
    {
        $this->builder->where('status', $status);
    }

    public function search(string $term): void
    {
        $this->whereLike(['title', 'description'], $term);
    }
}
