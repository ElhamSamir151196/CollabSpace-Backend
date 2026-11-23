<?php

namespace App\Actions\Project;

use App\Actions\Files\UploadAttachmentsAction;
use App\DTOs\Project\CreateProjectData;
use App\Enums\ProjectStatus;
use App\Events\ProjectCreated;
use App\Interfaces\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use App\Models\User;
use App\Services\Project\ProjectSlugGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Creates the project, assigns its teams and guests, then fires ProjectCreated.
 * The creator manages the project unless an admin picked another manager.
 */
class CreateProjectAction
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
        private readonly ProjectSlugGenerator $slugs,
        private readonly UploadAttachmentsAction $uploadFiles,
    ) {}

    public function handle(CreateProjectData $data, User $creator): Project
    {
        $project = DB::transaction(function () use ($data, $creator): Project {
            $project = $this->projects->create([
                'name' => $data->name,
                'slug' => $this->slugs->generate($data->name),
                'description' => $data->description,
                'type' => $data->type,
                'status' => $data->status,
                'priority' => $data->priority,
                'start_date' => $data->startDate,
                'deadline' => $data->deadline,
                'completed_at' => $data->status === ProjectStatus::Completed ? now() : null,
                'manager_id' => $data->managerId ?? $creator->id,
                'created_by' => $creator->id,
            ]);

            $this->projects->attachTeams($project, $data->teamIds);
            $this->projects->attachGuests($project, $data->guestIds, $creator);

            // Inside the transaction: if a file fails, no half-created project is left.
            if ($data->files !== []) {
                $this->uploadFiles->handle($project, $data->files, $creator);
            }

            return $project;
        });

        ProjectCreated::dispatch($project, $creator);

        return $project;
    }
}
