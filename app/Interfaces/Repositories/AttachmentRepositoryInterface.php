<?php

namespace App\Interfaces\Repositories;

use App\Filters\QueryFilter;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<Attachment>
 */
interface AttachmentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Project "Files" tab: files of the project and of all its tasks.
     *
     * @return LengthAwarePaginator<int, Attachment>
     */
    public function paginateForProject(Project $project, QueryFilter $filter, int $perPage = 15): LengthAwarePaginator;

    /**
     * @return LengthAwarePaginator<int, Attachment>
     */
    public function paginateForTask(Task $task, QueryFilter $filter, int $perPage = 15): LengthAwarePaginator;

    /**
     * Profile > Files: what the user uploaded (in projects they can still see).
     *
     * @return LengthAwarePaginator<int, Attachment>
     */
    public function paginateUploadedBy(User $user, QueryFilter $filter, int $perPage = 15): LengthAwarePaginator;

    /**
     * Dashboard "Recent Files".
     *
     * @return Collection<int, Attachment>
     */
    public function recentVisibleTo(User $user, int $limit = 5): Collection;
}
