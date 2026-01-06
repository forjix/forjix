<?php

declare(strict_types=1);

namespace Forjix\Orm\Relations;

use Forjix\Orm\Model;
use Forjix\Orm\ModelQueryBuilder;
use Forjix\Support\Collection;

class HasMany extends Relation
{
    protected string $foreignKey;
    protected string $localKey;

    public function __construct(ModelQueryBuilder $query, Model $parent, string $foreignKey, string $localKey)
    {
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;

        parent::__construct($query, $parent);
    }

    public function addConstraints(): void
    {
        $this->query->where($this->foreignKey, $this->parent->getAttribute($this->localKey));
    }

    public function addEagerConstraints(Collection $models): void
    {
        $keys = $this->getKeys($models, $this->localKey);

        $this->query->whereIn($this->getQualifiedForeignKeyName(), $keys);
    }

    public function match(Collection $models, Collection $results, string $relation): Collection
    {
        $dictionary = $this->buildDictionary($results);

        foreach ($models as $model) {
            $key = $model->getAttribute($this->localKey);

            $model->setRelation($relation, new Collection($dictionary[$key] ?? []));
        }

        return $models;
    }

    protected function buildDictionary(Collection $results): array
    {
        $dictionary = [];
        $foreignKey = $this->getPlainForeignKey();

        foreach ($results as $result) {
            $key = $result->getAttribute($foreignKey);
            $dictionary[$key][] = $result;
        }

        return $dictionary;
    }

    public function getResults(): Collection
    {
        if ($this->parent->getAttribute($this->localKey) === null) {
            return new Collection();
        }

        return $this->query->get();
    }

    protected function getQualifiedForeignKeyName(): string
    {
        return $this->foreignKey;
    }

    protected function getPlainForeignKey(): string
    {
        $segments = explode('.', $this->foreignKey);

        return end($segments);
    }

    public function save(Model $model): Model
    {
        $model->setAttribute($this->getPlainForeignKey(), $this->parent->getAttribute($this->localKey));
        $model->save();

        return $model;
    }

    public function saveMany(array $models): array
    {
        foreach ($models as $model) {
            $this->save($model);
        }

        return $models;
    }

    public function create(array $attributes): Model
    {
        $instance = $this->related->newFromBuilder($attributes);
        $instance->exists = false;

        return $this->save($instance);
    }

    public function createMany(array $records): Collection
    {
        $instances = [];

        foreach ($records as $record) {
            $instances[] = $this->create($record);
        }

        return new Collection($instances);
    }
}
