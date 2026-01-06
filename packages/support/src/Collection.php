<?php

declare(strict_types=1);

namespace Forjix\Support;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    protected array $items = [];

    public function __construct(array|self $items = [])
    {
        $this->items = $items instanceof self ? $items->all() : $items;
    }

    public static function make(array|self $items = []): static
    {
        return new static($items);
    }

    public static function wrap(mixed $value): static
    {
        return $value instanceof self
            ? new static($value)
            : new static(Arr::wrap($value));
    }

    public static function times(int $number, ?callable $callback = null): static
    {
        if ($number < 1) {
            return new static();
        }

        return (new static(range(1, $number)))
            ->when($callback !== null, fn($c) => $c->map($callback));
    }

    public function all(): array
    {
        return $this->items;
    }

    public function toArray(): array
    {
        return array_map(fn($value) => $value instanceof self ? $value->toArray() : $value, $this->items);
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function keys(): static
    {
        return new static(array_keys($this->items));
    }

    public function values(): static
    {
        return new static(array_values($this->items));
    }

    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        return Arr::first($this->items, $callback, $default);
    }

    public function last(?callable $callback = null, mixed $default = null): mixed
    {
        return Arr::last($this->items, $callback, $default);
    }

    public function get(string|int $key, mixed $default = null): mixed
    {
        return Arr::get($this->items, $key, $default);
    }

    public function has(string|int|array $key): bool
    {
        return Arr::has($this->items, $key);
    }

    public function put(string|int $key, mixed $value): static
    {
        $this->items[$key] = $value;

        return $this;
    }

    public function push(mixed ...$values): static
    {
        foreach ($values as $value) {
            $this->items[] = $value;
        }

        return $this;
    }

    public function pull(string|int $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->forget($key);

        return $value;
    }

    public function forget(string|int|array $keys): static
    {
        Arr::forget($this->items, $keys);

        return $this;
    }

    public function map(callable $callback): static
    {
        return new static(Arr::map($this->items, $callback));
    }

    public function filter(?callable $callback = null): static
    {
        if ($callback === null) {
            return new static(array_filter($this->items));
        }

        return new static(Arr::where($this->items, $callback));
    }

    public function reject(callable $callback): static
    {
        return $this->filter(fn($value, $key) => !$callback($value, $key));
    }

    public function each(callable $callback): static
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    public function pluck(string|array $value, ?string $key = null): static
    {
        return new static(Arr::pluck($this->items, $value, $key));
    }

    public function flatten(int $depth = PHP_INT_MAX): static
    {
        return new static(Arr::flatten($this->items, $depth));
    }

    public function unique(?callable $callback = null): static
    {
        if ($callback === null) {
            return new static(array_unique($this->items, SORT_REGULAR));
        }

        $exists = [];
        return $this->reject(function ($item, $key) use ($callback, &$exists) {
            $id = $callback($item, $key);
            if (in_array($id, $exists, true)) {
                return true;
            }
            $exists[] = $id;
            return false;
        });
    }

    public function merge(array|self $items): static
    {
        return new static(array_merge($this->items, $this->getArrayableItems($items)));
    }

    public function combine(array|self $values): static
    {
        return new static(array_combine($this->items, $this->getArrayableItems($values)));
    }

    public function only(array $keys): static
    {
        return new static(Arr::only($this->items, $keys));
    }

    public function except(array $keys): static
    {
        return new static(Arr::except($this->items, $keys));
    }

    public function slice(int $offset, ?int $length = null): static
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    public function take(int $limit): static
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }

        return $this->slice(0, $limit);
    }

    public function skip(int $count): static
    {
        return $this->slice($count);
    }

    public function chunk(int $size): static
    {
        if ($size <= 0) {
            return new static();
        }

        $chunks = [];
        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = new static($chunk);
        }

        return new static($chunks);
    }

    public function sort(?callable $callback = null): static
    {
        $items = $this->items;

        $callback ? uasort($items, $callback) : asort($items);

        return new static($items);
    }

    public function sortBy(callable|string $callback, bool $descending = false): static
    {
        $results = Arr::sortBy($this->items, $callback);

        return new static($descending ? array_reverse($results, true) : $results);
    }

    public function sortByDesc(callable|string $callback): static
    {
        return $this->sortBy($callback, true);
    }

    public function sortKeys(bool $descending = false): static
    {
        $items = $this->items;
        $descending ? krsort($items) : ksort($items);

        return new static($items);
    }

    public function reverse(): static
    {
        return new static(array_reverse($this->items, true));
    }

    public function flip(): static
    {
        return new static(array_flip($this->items));
    }

    public function groupBy(callable|string $groupBy): static
    {
        return new static(array_map(
            fn($group) => new static($group),
            Arr::groupBy($this->items, $groupBy)
        ));
    }

    public function keyBy(callable|string $keyBy): static
    {
        return new static(Arr::keyBy($this->items, $keyBy));
    }

    public function contains(mixed $key, mixed $operator = null, mixed $value = null): bool
    {
        if (func_num_args() === 1) {
            if (is_callable($key)) {
                return $this->first($key) !== null;
            }

            return in_array($key, $this->items, true);
        }

        return $this->contains($this->operatorForWhere(...func_get_args()));
    }

    public function search(mixed $value, bool $strict = false): int|string|false
    {
        if (is_callable($value)) {
            foreach ($this->items as $key => $item) {
                if ($value($item, $key)) {
                    return $key;
                }
            }

            return false;
        }

        return array_search($value, $this->items, $strict);
    }

    public function sum(?callable $callback = null): int|float
    {
        $callback = $this->valueRetriever($callback);

        return $this->reduce(fn($result, $item) => $result + $callback($item), 0);
    }

    public function avg(?callable $callback = null): int|float|null
    {
        if ($count = $this->count()) {
            return $this->sum($callback) / $count;
        }

        return null;
    }

    public function min(?callable $callback = null): mixed
    {
        $callback = $this->valueRetriever($callback);

        return $this->filter()->reduce(function ($result, $item) use ($callback) {
            $value = $callback($item);

            return $result === null || $value < $result ? $value : $result;
        });
    }

    public function max(?callable $callback = null): mixed
    {
        $callback = $this->valueRetriever($callback);

        return $this->filter()->reduce(function ($result, $item) use ($callback) {
            $value = $callback($item);

            return $result === null || $value > $result ? $value : $result;
        });
    }

    public function when(mixed $value, callable $callback, ?callable $default = null): static
    {
        $value = $value instanceof \Closure ? $value($this) : $value;

        if ($value) {
            return $callback($this, $value);
        } elseif ($default) {
            return $default($this, $value);
        }

        return $this;
    }

    public function unless(mixed $value, callable $callback, ?callable $default = null): static
    {
        return $this->when(!$value, $callback, $default);
    }

    public function tap(callable $callback): static
    {
        $callback($this);

        return $this;
    }

    public function pipe(callable $callback): mixed
    {
        return $callback($this);
    }

    public function implode(string $glue, ?string $key = null): string
    {
        if ($key !== null) {
            return implode($glue, $this->pluck($key)->all());
        }

        return implode($glue, $this->items);
    }

    public function join(string $glue, string $finalGlue = ''): string
    {
        if ($finalGlue === '') {
            return $this->implode($glue);
        }

        $count = $this->count();

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return (string) $this->first();
        }

        $collection = new static($this->items);
        $last = $collection->pull($collection->keys()->last());

        return $collection->implode($glue) . $finalGlue . $last;
    }

    public function diff(array|self $items): static
    {
        return new static(array_diff($this->items, $this->getArrayableItems($items)));
    }

    public function diffKeys(array|self $items): static
    {
        return new static(array_diff_key($this->items, $this->getArrayableItems($items)));
    }

    public function intersect(array|self $items): static
    {
        return new static(array_intersect($this->items, $this->getArrayableItems($items)));
    }

    public function intersectKeys(array|self $items): static
    {
        return new static(array_intersect_key($this->items, $this->getArrayableItems($items)));
    }

    public function where(string $key, mixed $operator = null, mixed $value = null): static
    {
        return $this->filter($this->operatorForWhere(...func_get_args()));
    }

    public function whereIn(string $key, array $values): static
    {
        return $this->filter(fn($item) => in_array(Arr::get($item, $key), $values, true));
    }

    public function whereNotIn(string $key, array $values): static
    {
        return $this->reject(fn($item) => in_array(Arr::get($item, $key), $values, true));
    }

    public function whereNull(?string $key = null): static
    {
        return $this->where($key, '===', null);
    }

    public function whereNotNull(?string $key = null): static
    {
        return $this->where($key, '!==', null);
    }

    protected function operatorForWhere(string $key, mixed $operator = null, mixed $value = null): callable
    {
        if (func_num_args() === 1) {
            $value = true;
            $operator = '=';
        }

        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return function ($item) use ($key, $operator, $value) {
            $retrieved = Arr::get($item, $key);

            return match ($operator) {
                '=', '==' => $retrieved == $value,
                '===', 'is' => $retrieved === $value,
                '!=', '<>' => $retrieved != $value,
                '!==' => $retrieved !== $value,
                '<' => $retrieved < $value,
                '>' => $retrieved > $value,
                '<=' => $retrieved <= $value,
                '>=' => $retrieved >= $value,
                default => $retrieved == $value,
            };
        };
    }

    protected function valueRetriever(?callable $value): callable
    {
        if ($value === null) {
            return fn($item) => $item;
        }

        return $value;
    }

    protected function getArrayableItems(mixed $items): array
    {
        return match (true) {
            is_array($items) => $items,
            $items instanceof self => $items->all(),
            $items instanceof \Traversable => iterator_to_array($items),
            default => (array) $items,
        };
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }
}
