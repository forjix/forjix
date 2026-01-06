<?php

declare(strict_types=1);

namespace Forjix\Orm;

use ArrayAccess;
use Forjix\Database\Connection;
use Forjix\Database\QueryBuilder;
use Forjix\Orm\Relations\BelongsTo;
use Forjix\Orm\Relations\BelongsToMany;
use Forjix\Orm\Relations\HasMany;
use Forjix\Orm\Relations\HasOne;
use Forjix\Support\Collection;
use Forjix\Support\Str;
use JsonSerializable;

abstract class Model implements ArrayAccess, JsonSerializable
{
    protected static ?Connection $connection = null;
    protected static string $resolver = '';

    protected string $table = '';
    protected string $primaryKey = 'id';
    protected string $keyType = 'int';
    public bool $incrementing = true;
    public bool $timestamps = true;

    protected array $attributes = [];
    protected array $original = [];
    protected array $relations = [];
    protected array $casts = [];
    protected array $hidden = [];
    protected array $visible = [];
    protected array $fillable = [];
    protected array $guarded = ['*'];
    protected bool $exists = false;

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
        $this->syncOriginal();
    }

    public static function setConnection(Connection $connection): void
    {
        static::$connection = $connection;
    }

    public static function getConnection(): Connection
    {
        return static::$connection;
    }

    public static function query(): ModelQueryBuilder
    {
        $instance = new static();

        return $instance->newQuery();
    }

    public function newQuery(): ModelQueryBuilder
    {
        return (new ModelQueryBuilder(static::$connection))
            ->setModel($this)
            ->from($this->getTable());
    }

    public function getTable(): string
    {
        return $this->table ?: Str::snake(class_basename($this)) . 's';
    }

    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    public function getKey(): mixed
    {
        return $this->getAttribute($this->primaryKey);
    }

    public function getKeyType(): string
    {
        return $this->keyType;
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    public function forceFill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    protected function isFillable(string $key): bool
    {
        if (in_array($key, $this->fillable, true)) {
            return true;
        }

        if (in_array('*', $this->guarded, true)) {
            return false;
        }

        return !in_array($key, $this->guarded, true);
    }

    public function getAttribute(string $key): mixed
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->castAttribute($key, $this->attributes[$key]);
        }

        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        if (method_exists($this, $key)) {
            return $this->getRelationValue($key);
        }

        return null;
    }

    public function setAttribute(string $key, mixed $value): static
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    protected function castAttribute(string $key, mixed $value): mixed
    {
        if (!isset($this->casts[$key]) || $value === null) {
            return $value;
        }

        return match ($this->casts[$key]) {
            'int', 'integer' => (int) $value,
            'real', 'float', 'double' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            'array', 'json' => is_array($value) ? $value : json_decode($value, true),
            'object' => is_object($value) ? $value : json_decode($value),
            'datetime' => $value instanceof \DateTimeInterface ? $value : new \DateTime($value),
            default => $value,
        };
    }

    protected function getRelationValue(string $key): mixed
    {
        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        $relation = $this->{$key}();
        $this->relations[$key] = $relation->getResults();

        return $this->relations[$key];
    }

    public function setRelation(string $relation, mixed $value): static
    {
        $this->relations[$relation] = $value;

        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getOriginal(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->original;
        }

        return $this->original[$key] ?? null;
    }

    public function syncOriginal(): static
    {
        $this->original = $this->attributes;

        return $this;
    }

    public function isDirty(?string $attribute = null): bool
    {
        if ($attribute !== null) {
            return ($this->attributes[$attribute] ?? null) !== ($this->original[$attribute] ?? null);
        }

        return $this->attributes !== $this->original;
    }

    public function getDirty(): array
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    public function save(): bool
    {
        if ($this->exists) {
            return $this->performUpdate();
        }

        return $this->performInsert();
    }

    protected function performInsert(): bool
    {
        if ($this->timestamps) {
            $this->freshTimestamp(static::CREATED_AT);
            $this->freshTimestamp(static::UPDATED_AT);
        }

        $attributes = $this->attributes;

        if ($this->incrementing) {
            unset($attributes[$this->primaryKey]);
        }

        $id = static::query()->insertGetId($attributes);

        if ($this->incrementing) {
            $this->setAttribute($this->primaryKey, $id);
        }

        $this->exists = true;
        $this->syncOriginal();

        return true;
    }

    protected function performUpdate(): bool
    {
        if (!$this->isDirty()) {
            return true;
        }

        if ($this->timestamps) {
            $this->freshTimestamp(static::UPDATED_AT);
        }

        $dirty = $this->getDirty();

        static::query()
            ->where($this->primaryKey, $this->getKey())
            ->update($dirty);

        $this->syncOriginal();

        return true;
    }

    protected function freshTimestamp(string $column): void
    {
        $this->setAttribute($column, date('Y-m-d H:i:s'));
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        static::query()
            ->where($this->primaryKey, $this->getKey())
            ->delete();

        $this->exists = false;

        return true;
    }

    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();

        return $model;
    }

    public static function find(int|string $id): ?static
    {
        return static::query()->find($id);
    }

    public static function findOrFail(int|string $id): static
    {
        $model = static::find($id);

        if ($model === null) {
            throw new ModelNotFoundException('Model not found');
        }

        return $model;
    }

    public static function all(array $columns = ['*']): Collection
    {
        return static::query()->get($columns);
    }

    public static function where(string $column, mixed $operator = null, mixed $value = null): ModelQueryBuilder
    {
        return static::query()->where($column, $operator, $value);
    }

    public static function first(): ?static
    {
        return static::query()->first();
    }

    public static function destroy(int|string|array $ids): int
    {
        $count = 0;

        foreach ((array) $ids as $id) {
            $model = static::find($id);
            if ($model && $model->delete()) {
                $count++;
            }
        }

        return $count;
    }

    // Relationships

    public function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): HasOne
    {
        $instance = new $related();
        $foreignKey = $foreignKey ?? Str::snake(class_basename($this)) . '_id';
        $localKey = $localKey ?? $this->primaryKey;

        return new HasOne(
            $instance->newQuery(),
            $this,
            $instance->getTable() . '.' . $foreignKey,
            $localKey
        );
    }

    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        $instance = new $related();
        $foreignKey = $foreignKey ?? Str::snake(class_basename($this)) . '_id';
        $localKey = $localKey ?? $this->primaryKey;

        return new HasMany(
            $instance->newQuery(),
            $this,
            $instance->getTable() . '.' . $foreignKey,
            $localKey
        );
    }

    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): BelongsTo
    {
        $instance = new $related();
        $foreignKey = $foreignKey ?? Str::snake(class_basename($related)) . '_id';
        $ownerKey = $ownerKey ?? $instance->primaryKey;

        return new BelongsTo(
            $instance->newQuery(),
            $this,
            $foreignKey,
            $ownerKey
        );
    }

    public function belongsToMany(
        string $related,
        ?string $table = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null,
        ?string $parentKey = null,
        ?string $relatedKey = null
    ): BelongsToMany {
        $instance = new $related();

        $table = $table ?? $this->joiningTable($related);
        $foreignPivotKey = $foreignPivotKey ?? Str::snake(class_basename($this)) . '_id';
        $relatedPivotKey = $relatedPivotKey ?? Str::snake(class_basename($related)) . '_id';
        $parentKey = $parentKey ?? $this->primaryKey;
        $relatedKey = $relatedKey ?? $instance->primaryKey;

        return new BelongsToMany(
            $instance->newQuery(),
            $this,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey,
            $relatedKey
        );
    }

    protected function joiningTable(string $related): string
    {
        $models = [
            Str::snake(class_basename($this)),
            Str::snake(class_basename($related)),
        ];

        sort($models);

        return implode('_', $models);
    }

    public function newFromBuilder(array $attributes = []): static
    {
        $model = new static();
        $model->exists = true;
        $model->forceFill($attributes);
        $model->syncOriginal();

        return $model;
    }

    public function toArray(): array
    {
        $attributes = $this->attributesToArray();
        $relations = $this->relationsToArray();

        return array_merge($attributes, $relations);
    }

    protected function attributesToArray(): array
    {
        $attributes = $this->attributes;

        if (!empty($this->hidden)) {
            $attributes = array_diff_key($attributes, array_flip($this->hidden));
        }

        if (!empty($this->visible)) {
            $attributes = array_intersect_key($attributes, array_flip($this->visible));
        }

        foreach ($this->casts as $key => $type) {
            if (isset($attributes[$key])) {
                $attributes[$key] = $this->castAttribute($key, $attributes[$key]);
            }
        }

        return $attributes;
    }

    protected function relationsToArray(): array
    {
        $relations = [];

        foreach ($this->relations as $key => $value) {
            if ($value instanceof Collection) {
                $relations[$key] = $value->map(fn($item) => $item instanceof self ? $item->toArray() : $item)->all();
            } elseif ($value instanceof self) {
                $relations[$key] = $value->toArray();
            } else {
                $relations[$key] = $value;
            }
        }

        return $relations;
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]) || isset($this->relations[$key]);
    }

    public function __unset(string $key): void
    {
        unset($this->attributes[$key], $this->relations[$key]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->{$offset});
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->{$offset};
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->{$offset} = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->{$offset});
    }

    public static function __callStatic(string $method, array $parameters): mixed
    {
        return (new static())->$method(...$parameters);
    }
}
