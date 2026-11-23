<?php

namespace App\Actions\Project;

use App\Interfaces\Repositories\ProjectRepositoryInterface;
use App\Models\Project;

class DeleteProjectAction
{
    public function __construct(private readonly ProjectRepositoryInterface $projects) {}

    /**
     * Soft delete: tasks, files and chats of the project stay in the database.
     */
    public function handle(Project $project): void
    {
        $this->projects->delete($project);
    }
}
