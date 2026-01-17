<?php

namespace Modules\Shared\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Contracts\RepositoryInterface;

/**
 * Base Repository
 *
 * Base repository implementation providing common CRUD operations.
 * Modules should extend this class for their specific repositories.
 *
 * @package Modules\Shared\Repositories
 * @version 1.0.0
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * The model class name
     *
     * @var string
     */
    protected string $model;

    /**
     * Find a model by ID
     *
     * @param int $id
     * @return Model|null
     */
    public function find(int $id): ?Model
    {
        return $this->getModel()::find($id);
    }

    /**
     * Find a model by UUID
     *
     * @param string $uuid
     * @return Model|null
     */
    public function findByUuid(string $uuid): ?Model
    {
        return $this->getModel()::where('uuid', $uuid)->first();
    }

    /**
     * Get all models
     *
     * @param array $columns
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->getModel()::all($columns);
    }

    /**
     * Create a new model
     *
     * @param array $attributes
     * @return Model
     */
    public function create(array $attributes): Model
    {
        return $this->getModel()::create($attributes);
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
        return $model->update($attributes);
    }

    /**
     * Delete a model
     *
     * @param Model $model
     * @return bool
     */
    public function delete(Model $model): bool
    {
        return $model->delete();
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
        return $this->getModel()::paginate($perPage, $columns);
    }

    /**
     * Get the model instance
     *
     * @return string
     */
    protected function getModel(): string
    {
        if (!isset($this->model)) {
            throw new \RuntimeException('Model class must be defined in repository');
        }

        return $this->model;
    }
}

