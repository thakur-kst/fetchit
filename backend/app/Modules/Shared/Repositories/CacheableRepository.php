<?php

namespace Modules\Shared\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Contracts\RepositoryInterface;
use Modules\Shared\Services\CacheService;
use Modules\Shared\Support\CacheKeyGenerator;

/**
 * Cacheable Repository
 *
 * Decorator pattern implementation that adds caching to any repository.
 * Wraps existing repository implementations with caching layer.
 *
 * @package Modules\Shared\Repositories
 * @version 1.0.0
 */
class CacheableRepository implements RepositoryInterface
{
    private CacheService $cacheService;
    private CacheKeyGenerator $keyGenerator;
    private string $module;
    private string $entity;

    public function __construct(
        private RepositoryInterface $repository,
        string $module,
        string $entity,
        ?CacheService $cacheService = null
    ) {
        $this->module = strtolower($module);
        $this->entity = strtolower($entity);
        $this->cacheService = $cacheService ?? app(CacheService::class);
        $this->keyGenerator = new CacheKeyGenerator();
    }

    /**
     * Find a model by ID
     *
     * @param int $id
     * @return Model|null
     */
    public function find(int $id): ?Model
    {
        $key = $this->keyGenerator->entityById($this->module, $this->entity, $id);

        return $this->cacheService->remember($key, function () use ($id) {
            return $this->repository->find($id);
        });
    }

    /**
     * Find a model by UUID
     *
     * @param string $uuid
     * @return Model|null
     */
    public function findByUuid(string $uuid): ?Model
    {
        $key = $this->keyGenerator->entityByUuid($this->module, $this->entity, $uuid);

        return $this->cacheService->remember($key, function () use ($uuid) {
            return $this->repository->findByUuid($uuid);
        });
    }

    /**
     * Get all models
     *
     * @param array $columns
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection
    {
        $key = $this->keyGenerator->list($this->module, $this->entity);

        return $this->cacheService->remember($key, function () use ($columns) {
            return $this->repository->all($columns);
        });
    }

    /**
     * Create a new model
     *
     * @param array $attributes
     * @return Model
     */
    public function create(array $attributes): Model
    {
        $model = $this->repository->create($attributes);
        $this->invalidateCache($model);
        return $model;
    }

    /**
     * Update a model
     *
     * @param Model $model
     * @param array $attributes
     * @return bool
     */
    public function update(Model $model, array $attributes): bool
    {
        $result = $this->repository->update($model, $attributes);
        $this->invalidateCache($model);
        return $result;
    }

    /**
     * Delete a model
     *
     * @param Model $model
     * @return bool
     */
    public function delete(Model $model): bool
    {
        $result = $this->repository->delete($model);
        $this->invalidateCache($model);
        return $result;
    }

    /**
     * Paginate results
     *
     * @param int $perPage
     * @param array $columns
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        // Pagination is typically not cached due to dynamic nature
        return $this->repository->paginate($perPage, $columns);
    }

    /**
     * Invalidate cache for a model
     *
     * @param Model $model
     * @return void
     */
    private function invalidateCache(Model $model): void
    {
        // Invalidate by ID
        if (isset($model->id)) {
            $this->cacheService->forget(
                $this->keyGenerator->entityById($this->module, $this->entity, $model->id)
            );
        }

        // Invalidate by UUID
        if (isset($model->uuid)) {
            $this->cacheService->forget(
                $this->keyGenerator->entityByUuid($this->module, $this->entity, $model->uuid)
            );
        }

        // Invalidate list cache
        $this->cacheService->forget(
            $this->keyGenerator->list($this->module, $this->entity)
        );

        // Invalidate user-specific cache if user_id exists
        if (isset($model->user_id)) {
            $this->cacheService->forget(
                $this->keyGenerator->user($this->module, $this->entity, $model->user_id)
            );
        }
    }
}

