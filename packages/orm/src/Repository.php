<?php

declare(strict_types=1);

namespace Forjix\Orm;

use Forjix\Support\Collection;

abstract class Repository
{
    protected string $model;

    public function __construct()
    {
        if (!isset($this->model)) {
            throw new \RuntimeException('Repository must define a $model property');
        }
    }

    protected function query(): ModelQueryBuilder
    {
        return $this->model::query();
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->query()->get($columns);
    }

    public function find(int|string $id, array $columns = ['*']): ?Model
    {
        return $this->query()->find($id, $columns);
    }

    public function findOrFail(int|string $id, array $columns = ['*']): Model
    {
        return $this->query()->findOrFail($id, $columns);
    }

    public function findBy(string $field, mixed $value, array $columns = ['*']): ?Model
    {
        return $this->query()->where($field, $value)->first($columns);
    }

    public function findAllBy(string $field, mixed $value, array $columns = ['*']): Collection
    {
        return $this->query()->where($field, $value)->get($columns);
    }

    public function findWhere(array $where, array $columns = ['*']): Collection
    {
        $query = $this->query();

        foreach ($where as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->get($columns);
    }

    public function create(array $attributes): Model
    {
        return $this->model::create($attributes);
    }

    public function update(int|string $id, array $attributes): ?Model
    {
        $model = $this->find($id);

        if ($model === null) {
            return null;
        }

        $model->fill($attributes)->save();

        return $model;
    }

    public function delete(int|string $id): bool
    {
        $model = $this->find($id);

        if ($model === null) {
            return false;
        }

        return $model->delete();
    }

    public function paginate(int $perPage = 15, int $page = 1, array $columns = ['*']): array
    {
        return $this->query()->select($columns)->paginate($perPage, $page);
    }

    public function count(): int
    {
        return $this->query()->count();
    }

    public function exists(int|string $id): bool
    {
        return $this->query()->where('id', $id)->exists();
    }

    public function firstOrCreate(array $attributes, array $values = []): Model
    {
        return $this->query()->firstOrCreate($attributes, $values);
    }

    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        return $this->query()->updateOrCreate($attributes, $values);
    }

    public function with(string|array $relations): ModelQueryBuilder
    {
        return $this->query()->with($relations);
    }

    public function orderBy(string $column, string $direction = 'asc'): ModelQueryBuilder
    {
        return $this->query()->orderBy($column, $direction);
    }

    public function latest(string $column = 'created_at'): ModelQueryBuilder
    {
        return $this->query()->latest($column);
    }

    public function oldest(string $column = 'created_at'): ModelQueryBuilder
    {
        return $this->query()->oldest($column);
    }
}
