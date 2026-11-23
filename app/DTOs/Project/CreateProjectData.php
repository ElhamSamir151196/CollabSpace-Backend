<?php

namespace App\DTOs\Project;

use App\Enums\Priority;
use App\Enums\ProjectStatus;
use App\Http\Requests\V1\Project\StoreProjectRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;

final readonly class CreateProjectData
{
    /**
     * @param  list<int>  $teamIds
     * @param  list<int>  $guestIds
     * @param  list<UploadedFile>  $files
     */
    public function __construct(
        public string $name,
        public ?string $description,
        public ?string $type,
        public ProjectStatus $status,
        public Priority $priority,
        public CarbonImmutable $startDate,
        public CarbonImmutable $deadline,
        public ?int $managerId = null,
        public array $teamIds = [],
        public array $guestIds = [],
        public array $files = [],
    ) {}

    public static function fromRequest(StoreProjectRequest $request): self
    {
        return new self(
            name: $request->string('name')->trim()->toString(),
            description: $request->filled('description') ? $request->string('description')->trim()->toString() : null,
            type: $request->filled('type') ? $request->string('type')->trim()->toString() : null,
            status: $request->enum('status', ProjectStatus::class) ?? ProjectStatus::Pending,
            priority: $request->enum('priority', Priority::class) ?? Priority::Medium,
            startDate: CarbonImmutable::parse($request->string('start_date')->toString()),
            deadline: CarbonImmutable::parse($request->string('deadline')->toString()),
            managerId: $request->filled('manager_id') ? $request->integer('manager_id') : null,
            teamIds: self::ids($request->validated('team_ids', [])),
            guestIds: self::ids($request->validated('guest_ids', [])),
            files: $request->uploadedFiles(),
        );
    }

    /**
     * @param  array<mixed>  $ids
     * @return list<int>
     */
    private static function ids(array $ids): array
    {
        return array_values(array_unique(array_map('intval', $ids)));
    }
}
