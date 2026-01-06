<?php

declare(strict_types=1);

namespace Forjix\Orm\Relations;

use Forjix\Orm\Model;
use Forjix\Orm\ModelQueryBuilder;
use Forjix\Support\Collection;

class BelongsToMany extends Relation
{
    protected string $table;
    protected string $foreignPivotKey;
    protected string $relatedPivotKey;
    protected string $parentKey;
    protected string $relatedKey;
    protected array $pivotColumns = [];

    public function __construct(
        ModelQueryBuilder $query,
        Model $parent,
        string $table,
        string $foreignPivotKey,
        string $relatedPivotKey,
        string $parentKey,
        string $relatedKey
    ) {
        $this->table = $table;
        $this->foreignPivotKey = $foreignPivotKey;
        $this->relatedPivotKey = $relatedPivotKey;
        $this->parentKey = $parentKey;
        $this->relatedKey = $relatedKey;

        parent::__construct($query, $parent);
    }

    public function addConstraints(): void
    {
        $this->performJoin();

        $this->query->where(
            "{$this->table}.{$this->foreignPivotKey}",
            $this->parent->getAttribute($this->parentKey)
        );
    }

    protected function performJoin(): void
    {
        $this->query->join(
            $this->table,
            "{$this->related->getTable()}.{$this->relatedKey}",
            '=',
            "{$this->table}.{$this->relatedPivotKey}"
        );

        $this->query->select("{$this->related->getTable()}.*");

        foreach ($this->pivotColumns as $column) {
            $this->query->addSelect("{$this->table}.{$column} as pivot_{$column}");
        }
    }

    public function addEagerConstraints(Collection $models): void
    {
        $keys = $this->getKeys($models, $this->parentKey);

        $this->query->whereIn("{$this->table}.{$this->foreignPivotKey}", $keys);
    }

    public function match(Collection $models, Collection $results, string $relation): Collection
    {
        $dictionary = $this->buildDictionary($results);

        foreach ($models as $model) {
            $key = $model->getAttribute($this->parentKey);

            $model->setRelation($relation, new Collection($dictionary[$key] ?? []));
        }

        return $models;
    }

    protected function buildDictionary(Collection $results): array
    {
        $dictionary = [];

        foreach ($results as $result) {
            $key = $result->pivot[$this->foreignPivotKey] ?? null;

            if ($key !== null) {
                $dictionary[$key][] = $result;
            }
        }

        return $dictionary;
    }

    public function getResults(): Collection
    {
        if ($this->parent->getAttribute($this->parentKey) === null) {
            return new Collection();
        }

        return $this->query->get();
    }

    public function getEager(): Collection
    {
        $results = parent::getEager();

        // Transform pivot data
        return $results->map(function ($model) {
            $model->pivot = $this->extractPivotAttributes($model);
            return $model;
        });
    }

    protected function extractPivotAttributes(Model $model): array
    {
        $pivot = [];
        $attributes = $model->getAttributes();

        foreach ($attributes as $key => $value) {
            if (str_starts_with($key, 'pivot_')) {
                $pivot[substr($key, 6)] = $value;
            }
        }

        $pivot[$this->foreignPivotKey] = $attributes["pivot_{$this->foreignPivotKey}"] ?? null;
        $pivot[$this->relatedPivotKey] = $attributes["pivot_{$this->relatedPivotKey}"] ?? null;

        return $pivot;
    }

    public function withPivot(string|array ...$columns): static
    {
        $columns = array_merge(...array_map(fn($c) => (array) $c, $columns));
        $this->pivotColumns = array_merge($this->pivotColumns, $columns);

        return $this;
    }

    public function attach(int|string|array $id, array $attributes = []): void
    {
        $ids = (array) $id;

        foreach ($ids as $relatedId) {
            $record = array_merge([
                $this->foreignPivotKey => $this->parent->getAttribute($this->parentKey),
                $this->relatedPivotKey => $relatedId,
            ], $attributes);

            $this->parent::getConnection()
                ->table($this->table)
                ->insert($record);
        }
    }

    public function detach(int|string|array|null $ids = null): int
    {
        $query = $this->parent::getConnection()
            ->table($this->table)
            ->where($this->foreignPivotKey, $this->parent->getAttribute($this->parentKey));

        if ($ids !== null) {
            $query->whereIn($this->relatedPivotKey, (array) $ids);
        }

        return $query->delete();
    }

    public function sync(array $ids, bool $detaching = true): array
    {
        $changes = ['attached' => [], 'detached' => [], 'updated' => []];

        $current = $this->parent::getConnection()
            ->table($this->table)
            ->where($this->foreignPivotKey, $this->parent->getAttribute($this->parentKey))
            ->pluck($this->relatedPivotKey)
            ->all();

        $records = $this->formatRecordsList($ids);

        $detach = array_diff($current, array_keys($records));

        if ($detaching && count($detach) > 0) {
            $this->detach($detach);
            $changes['detached'] = $detach;
        }

        $attach = array_diff(array_keys($records), $current);

        foreach ($attach as $id) {
            $this->attach($id, $records[$id]);
            $changes['attached'][] = $id;
        }

        return $changes;
    }

    protected function formatRecordsList(array $records): array
    {
        $formatted = [];

        foreach ($records as $id => $attributes) {
            if (is_numeric($id)) {
                $formatted[$attributes] = [];
            } else {
                $formatted[$id] = $attributes;
            }
        }

        return $formatted;
    }

    public function toggle(array $ids): array
    {
        $changes = ['attached' => [], 'detached' => []];

        $current = $this->parent::getConnection()
            ->table($this->table)
            ->where($this->foreignPivotKey, $this->parent->getAttribute($this->parentKey))
            ->pluck($this->relatedPivotKey)
            ->all();

        $records = $this->formatRecordsList($ids);

        foreach (array_keys($records) as $id) {
            if (in_array($id, $current, true)) {
                $this->detach($id);
                $changes['detached'][] = $id;
            } else {
                $this->attach($id, $records[$id]);
                $changes['attached'][] = $id;
            }
        }

        return $changes;
    }

    public function updateExistingPivot(int|string $id, array $attributes): int
    {
        return $this->parent::getConnection()
            ->table($this->table)
            ->where($this->foreignPivotKey, $this->parent->getAttribute($this->parentKey))
            ->where($this->relatedPivotKey, $id)
            ->update($attributes);
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getForeignPivotKeyName(): string
    {
        return $this->foreignPivotKey;
    }

    public function getRelatedPivotKeyName(): string
    {
        return $this->relatedPivotKey;
    }
}
