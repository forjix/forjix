<?php

declare(strict_types=1);

namespace Forjix\Orm\Relations;

use Forjix\Orm\Model;
use Forjix\Orm\ModelQueryBuilder;
use Forjix\Support\Collection;

class HasOne extends Relation
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

            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key]);
            }
        }

        return $models;
    }

    protected function buildDictionary(Collection $results): array
    {
        $dictionary = [];
        $foreignKey = $this->getPlainForeignKey();

        foreach ($results as $result) {
            $dictionary[$result->getAttribute($foreignKey)] = $result;
        }

        return $dictionary;
    }

    public function getResults(): ?Model
    {
        if ($this->parent->getAttribute($this->localKey) === null) {
            return null;
        }

        return $this->query->first();
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

    public function create(array $attributes): Model
    {
        $instance = $this->related->newFromBuilder($attributes);
        $instance->exists = false;

        return $this->save($instance);
    }
}
