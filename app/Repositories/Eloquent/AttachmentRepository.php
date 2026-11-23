<?php

namespace App\Repositories\Eloquent;

use App\Filters\QueryFilter;
use App\Interfaces\Repositories\AttachmentRepositoryInterface;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<Attachment>
 */
class AttachmentRepository extends BaseRepository implements AttachmentRepositoryInterface
{
    /** @var list<string> */
    private const RELATIONS = ['uploader', 'project', 'attachable'];

    public function __construct(Attachment $model)
    {
        parent::__construct($model);
    }

    public function paginateForProject(Project $project, QueryFilter $filter, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginateQuery($this->query()->where('project_id', $project->id), $filter, $perPage);
    }

    public function paginateForTask(Task $task, QueryFilter $filter, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query()
            ->where('attachable_type', Task::class)
            ->where('attachable_id', $task->id);

        return $this->paginateQuery($query, $filter, $perPage);
    }

    public function paginateUploadedBy(User $user, QueryFilter $filter, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query()->where('uploaded_by', $user->id)->visibleTo($user);

        return $this->paginateQuery($query, $filter, $perPage);
    }

    public function recentVisibleTo(User $user, int $limit = 5): Collection
    {
        return $this->query()
            ->visibleTo($user)
            ->with(self::RELATIONS)
            ->latest()
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  Builder<Attachment>  $query
     * @return LengthAwarePaginator<int, Attachment>
     */
    private function paginateQuery(Builder $query, QueryFilter $filter, int $perPage): LengthAwarePaginator
    {
        $query->with(self::RELATIONS);

        $filter->apply($query);

        return $query->paginate(max(1, min($perPage, static::MAX_PER_PAGE)));
    }
}
