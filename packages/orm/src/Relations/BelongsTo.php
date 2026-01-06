<?php

declare(strict_types=1);

namespace Forjix\Orm\Relations;

use Forjix\Orm\Model;
use Forjix\Orm\ModelQueryBuilder;
use Forjix\Support\Collection;

class BelongsTo extends Relation
{
    protected string $foreignKey;
    protected string $ownerKey;

    public function __construct(ModelQueryBuilder $query, Model $child, string $foreignKey, string $ownerKey)
    {
        $this->foreignKey = $foreignKey;
        $this->ownerKey = $ownerKey;

        parent::__construct($query, $child);
    }

    public function addConstraints(): void
    {
        $this->query->where(
            $this->ownerKey,
            $this->parent->getAttribute($this->foreignKey)
        );
    }

    public function addEagerConstraints(Collection $models): void
    {
        $keys = $this->getKeys($models, $this->foreignKey);

        $this->query->whereIn($this->ownerKey, $keys);
    }

    public function match(Collection $models, Collection $results, string $relation): Collection
    {
        $dictionary = $this->buildDictionary($results);

        foreach ($models as $model) {
            $key = $model->getAttribute($this->foreignKey);

            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key]);
            }
        }

        return $models;
    }

    protected function buildDictionary(Collection $results): array
    {
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->getAttribute($this->ownerKey)] = $result;
        }

        return $dictionary;
    }

    public function getResults(): ?Model
    {
        if ($this->parent->getAttribute($this->foreignKey) === null) {
            return null;
        }

        return $this->query->first();
    }

    public function associate(Model $model): Model
    {
        $this->parent->setAttribute($this->foreignKey, $model->getAttribute($this->ownerKey));

        return $this->parent->setRelation($this->getRelationName(), $model);
    }

    public function dissociate(): Model
    {
        $this->parent->setAttribute($this->foreignKey, null);

        return $this->parent->setRelation($this->getRelationName(), null);
    }

    protected function getRelationName(): string
    {
        // Get calling method name from parent class
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        return $trace[2]['function'] ?? 'relation';
    }

    public function getForeignKeyName(): string
    {
        return $this->foreignKey;
    }

    public function getOwnerKeyName(): string
    {
        return $this->ownerKey;
    }
}
