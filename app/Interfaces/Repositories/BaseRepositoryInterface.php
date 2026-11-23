<?php

namespace App\Interfaces\Repositories;

use App\Filters\QueryFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Common data-access contract. Each module interface extends this one
 * and adds its own queries (e.g. ProjectRepositoryInterface::visibleTo()).
 *
 * @template TModel of Model
 */
interface BaseRepositoryInterface
{
    /**
     * @param  list<string>  $with
     * @return Collection<int, TModel>
     */
    public function all(array $with = []): Collection;

    /**
     * @param  list<string>  $with
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(?QueryFilter $filter = null, int $perPage = 15, array $with = []): LengthAwarePaginator;

    /**
     * @param  list<string>  $with
     * @return TModel|null
     */
    public function find(int|string $id, array $with = []): ?Model;

    /**
     * @param  list<string>  $with
     * @return TModel
     */
    public function findOrFail(int|string $id, array $with = []): Model;

    /**
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function create(array $attributes): Model;

    /**
     * @param  TModel  $model
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function update(Model $model, array $attributes): Model;

    /**
     * @param  TModel  $model
     */
    public function delete(Model $model): bool;
}
