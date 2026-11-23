<?php

namespace App\DTOs\Project;

use App\Enums\Priority;
use App\Enums\ProjectStatus;
use App\Http\Requests\V1\Project\UpdateProjectRequest;

/**
 * Only the fields the client actually sent (partial update).
 * Teams and guests are managed through their own endpoints.
 */
final readonly class UpdateProjectData
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    private function __construct(public array $attributes) {}

    public static function fromRequest(UpdateProjectRequest $request): self
    {
        $attributes = $request->safe()->only([
            'name',
            'description',
            'type',
            'status',
            'priority',
            'start_date',
            'deadline',
            'manager_id',
        ]);

        if (isset($attributes['status'])) {
            $attributes['status'] = ProjectStatus::from($attributes['status']);
        }

        if (isset($attributes['priority'])) {
            $attributes['priority'] = Priority::from($attributes['priority']);
        }

        return new self($attributes);
    }

    public function status(): ?ProjectStatus
    {
        $status = $this->attributes['status'] ?? null;

        return $status instanceof ProjectStatus ? $status : null;
    }
}
