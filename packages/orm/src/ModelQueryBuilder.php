<?php

declare(strict_types=1);

namespace Forjix\Orm;

use Forjix\Database\QueryBuilder;
use Forjix\Support\Collection;

class ModelQueryBuilder extends QueryBuilder
{
    protected ?Model $model = null;
    protected array $eagerLoad = [];

    public function setModel(Model $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getModel(): ?Model
    {
        return $this->model;
    }

    public function with(string|array $relations): static
    {
        $this->eagerLoad = array_merge(
            $this->eagerLoad,
            is_string($relations) ? func_get_args() : $relations
        );

        return $this;
    }

    public function get(array|string $columns = ['*']): Collection
    {
        $results = parent::get($columns);

        $models = $results->map(function ($attributes) {
            return $this->model->newFromBuilder((array) $attributes);
        });

        if (!empty($this->eagerLoad)) {
            $models = $this->eagerLoadRelations($models);
        }

        return $models;
    }

    public function first(array|string $columns = ['*']): ?Model
    {
        $result = parent::first($columns);

        if ($result === null) {
            return null;
        }

        $model = $this->model->newFromBuilder((array) $result);

        if (!empty($this->eagerLoad)) {
            $models = $this->eagerLoadRelations(new Collection([$model]));
            return $models->first();
        }

        return $model;
    }

    public function find(int|string $id, array|string $columns = ['*']): ?Model
    {
        return $this->where($this->model->getKeyName(), $id)->first($columns);
    }

    public function findOrFail(int|string $id, array|string $columns = ['*']): Model
    {
        $model = $this->find($id, $columns);

        if ($model === null) {
            throw new ModelNotFoundException('Model not found');
        }

        return $model;
    }

    public function firstOrFail(array|string $columns = ['*']): Model
    {
        $model = $this->first($columns);

        if ($model === null) {
            throw new ModelNotFoundException('Model not found');
        }

        return $model;
    }

    public function firstOrCreate(array $attributes, array $values = []): Model
    {
        $instance = $this->where($attributes)->first();

        if ($instance !== null) {
            return $instance;
        }

        $modelClass = get_class($this->model);

        return $modelClass::create(array_merge($attributes, $values));
    }

    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        $instance = $this->where($attributes)->first();

        if ($instance !== null) {
            $instance->fill($values)->save();
            return $instance;
        }

        $modelClass = get_class($this->model);

        return $modelClass::create(array_merge($attributes, $values));
    }

    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $total = $this->clone()->count();
        $items = $this->forPage($page, $perPage)->get();

        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
            'from' => ($page - 1) * $perPage + 1,
            'to' => min($page * $perPage, $total),
        ];
    }

    protected function eagerLoadRelations(Collection $models): Collection
    {
        foreach ($this->eagerLoad as $relation) {
            $models = $this->eagerLoadRelation($models, $relation);
        }

        return $models;
    }

    protected function eagerLoadRelation(Collection $models, string $name): Collection
    {
        $first = $models->first();

        if ($first === null || !method_exists($first, $name)) {
            return $models;
        }

        $relation = $first->{$name}();
        $relation->addEagerConstraints($models);

        $results = $relation->getEager();
        $relation->match($models, $results, $name);

        return $models;
    }

    public function chunk(int $count, callable $callback): bool
    {
        $page = 1;

        do {
            $results = $this->forPage($page, $count)->get();

            if ($results->isEmpty()) {
                break;
            }

            if ($callback($results, $page) === false) {
                return false;
            }

            $page++;
        } while ($results->count() === $count);

        return true;
    }

    public function each(callable $callback, int $count = 1000): bool
    {
        return $this->chunk($count, function ($results, $page) use ($callback) {
            foreach ($results as $key => $value) {
                if ($callback($value, $key) === false) {
                    return false;
                }
            }
        });
    }

    public function pluck(string $column, ?string $key = null): Collection
    {
        $results = $this->get($key ? [$column, $key] : [$column]);

        if ($key) {
            return $results->keyBy($key)->map(fn($item) => $item->{$column});
        }

        return $results->map(fn($item) => $item->{$column});
    }

    public function value(string $column): mixed
    {
        $result = $this->first([$column]);

        return $result ? $result->{$column} : null;
    }
}
