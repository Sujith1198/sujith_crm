<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * BaseRepository
 * Generic Eloquent implementation of BaseRepositoryInterface.
 * All other repositories extend this class and override as needed.
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    /** Array of eager-loaded relations (chainable via with()) */
    protected array $withs = [];

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    protected function query()
    {
        $query = $this->model->newQuery();
        if (! empty($this->withs)) {
            $query->with($this->withs);
        }
        $this->withs = []; // Reset after use
        return $query;
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->query()->get($columns);
    }

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->query()->paginate($perPage, $columns);
    }

    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->query()->find($id, $columns);
    }

    public function findOrFail(int $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Model
    {
        $record = $this->findOrFail($id);
        $record->update($data);
        return $record->fresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->findOrFail($id)->delete();
    }

    public function findBy(string $field, mixed $value): ?Model
    {
        return $this->query()->where($field, $value)->first();
    }

    public function findAllBy(string $field, mixed $value): Collection
    {
        return $this->query()->where($field, $value)->get();
    }

    public function with(array $relations): static
    {
        $this->withs = $relations;
        return $this;
    }
}
