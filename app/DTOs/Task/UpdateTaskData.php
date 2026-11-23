<?php

namespace App\DTOs\Task;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Http\Requests\V1\Task\UpdateTaskRequest;

/**
 * Only the fields the client actually sent (partial update).
 * $assigneeIds is null when the assignees were not sent (leave them as they are).
 */
final readonly class UpdateTaskData
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>|null  $assigneeIds
     */
    private function __construct(
        public array $attributes,
        public ?TaskStatus $status,
        public ?array $assigneeIds,
    ) {}

    public static function fromRequest(UpdateTaskRequest $request): self
    {
        $attributes = $request->safe()->only([
            'team_id',
            'title',
            'description',
            'priority',
            'start_date',
            'due_date',
            'reviewer_id',
        ]);

        if (isset($attributes['priority'])) {
            $attributes['priority'] = Priority::from($attributes['priority']);
        }

        return new self(
            attributes: $attributes,
            status: $request->enum('status', TaskStatus::class),
            assigneeIds: $request->has('assignee_ids') ? $request->assigneeIds() : null,
        );
    }
}
