<?php

declare(strict_types=1);

namespace Forjix\Orm\Relations;

use Forjix\Orm\Model;
use Forjix\Orm\ModelQueryBuilder;
use Forjix\Support\Collection;

abstract class Relation
{
    protected ModelQueryBuilder $query;
    protected Model $parent;
    protected Model $related;

    public function __construct(ModelQueryBuilder $query, Model $parent)
    {
        $this->query = $query;
        $this->parent = $parent;
        $this->related = $query->getModel();

        $this->addConstraints();
    }

    abstract public function addConstraints(): void;

    abstract public function addEagerConstraints(Collection $models): void;

    abstract public function match(Collection $models, Collection $results, string $relation): Collection;

    abstract public function getResults(): mixed;

    public function getEager(): Collection
    {
        return $this->query->get();
    }

    public function getQuery(): ModelQueryBuilder
    {
        return $this->query;
    }

    public function getParent(): Model
    {
        return $this->parent;
    }

    public function getRelated(): Model
    {
        return $this->related;
    }

    protected function getKeys(Collection $models, string $key): array
    {
        return $models
            ->pluck($key)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function __call(string $method, array $parameters): mixed
    {
        $result = $this->query->{$method}(...$parameters);

        if ($result === $this->query) {
            return $this;
        }

        return $result;
    }
}
