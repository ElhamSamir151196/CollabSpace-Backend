<?php

namespace App\Actions\Project;

use App\DTOs\Project\UpdateProjectData;
use App\Enums\ProjectStatus;
use App\Interfaces\Repositories\ProjectRepositoryInterface;
use App\Models\Project;

class UpdateProjectAction
{
    public function __construct(private readonly ProjectRepositoryInterface $projects) {}

    public function handle(Project $project, UpdateProjectData $data): Project
    {
        $attributes = $data->attributes;

        if ($attributes === []) {
            return $project;
        }

        // Keep completed_at in step with the status.
        $status = $data->status();

        if ($status !== null && $status !== $project->status) {
            $attributes['completed_at'] = $status === ProjectStatus::Completed ? now() : null;
        }

        return $this->projects->update($project, $attributes);
    }
}
