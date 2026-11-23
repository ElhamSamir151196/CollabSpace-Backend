<?php

namespace App\Repositories\Eloquent;

use App\Filters\QueryFilter;
use App\Interfaces\Repositories\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent implementation of the common repository methods.
 *
 * Child example:
 *   class ProjectRepository extends BaseRepository implements ProjectRepositoryInterface
 *   {
 *       public function __construct(Project $model) { parent::__construct($model); }
 *   }
 *
 * @template TModel of Model
 *
 * @implements BaseRepositoryInterface<TModel>
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    /** Upper bound for ?per_page so a client can't ask for 100k rows. */
    protected const MAX_PER_PAGE = 100;

    /**
     * @param  TModel  $model
     */
    public function __construct(protected Model $model) {}

    /**
     * @return Builder<TModel>
     */
    protected function query(): Builder
    {
        return $this->model->newQuery();
    }

    public function all(array $with = []): Collection
    {
        return $this->query()->with($with)->get();
    }

    public function paginate(?QueryFilter $filter = null, int $perPage = 15, array $with = []): LengthAwarePaginator
    {
        $query = $this->query()->with($with);

        if ($filter !== null) {
            $filter->apply($query);
        }

        return $query->paginate(max(1, min($perPage, static::MAX_PER_PAGE)));
    }

    public function find(int|string $id, array $with = []): ?Model
    {
        return $this->query()->with($with)->find($id);
    }

    public function findOrFail(int|string $id, array $with = []): Model
    {
        return $this->query()->with($with)->findOrFail($id);
    }

    public function create(array $attributes): Model
    {
        return $this->query()->create($attributes);
    }

    public function update(Model $model, array $attributes): Model
    {
        $model->update($attributes);

        return $model->refresh();
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }
}
