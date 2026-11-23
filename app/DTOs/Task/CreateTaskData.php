<?php

namespace App\DTOs\Task;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Http\Requests\V1\Task\StoreTaskRequest;
use Illuminate\Http\UploadedFile;

final readonly class CreateTaskData
{
    /**
     * @param  list<int>  $assigneeIds
     * @param  list<UploadedFile>  $files
     */
    public function __construct(
        public int $projectId,
        public int $teamId,
        public string $title,
        public ?string $description,
        public TaskStatus $status,
        public Priority $priority,
        public ?string $startDate,
        public ?string $dueDate,
        public ?int $reviewerId,
        public array $assigneeIds = [],
        public array $files = [],
    ) {}

    public static function fromRequest(StoreTaskRequest $request): self
    {
        return new self(
            projectId: $request->project()->id,
            teamId: $request->integer('team_id'),
            title: $request->string('title')->trim()->toString(),
            description: $request->filled('description') ? $request->string('description')->trim()->toString() : null,
            status: $request->enum('status', TaskStatus::class) ?? TaskStatus::Pending,
            priority: $request->enum('priority', Priority::class) ?? Priority::Medium,
            startDate: $request->filled('start_date') ? $request->string('start_date')->toString() : null,
            dueDate: $request->filled('due_date') ? $request->string('due_date')->toString() : null,
            reviewerId: $request->filled('reviewer_id') ? $request->integer('reviewer_id') : null,
            assigneeIds: $request->assigneeIds(),
            files: $request->uploadedFiles(),
        );
    }
}
