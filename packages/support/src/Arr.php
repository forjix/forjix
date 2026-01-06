<?php

declare(strict_types=1);

namespace Forjix\Support;

class Arr
{
    public static function get(array $array, string|int|null $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $array;
        }

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (!str_contains((string) $key, '.')) {
            return $array[$key] ?? $default;
        }

        foreach (explode('.', (string) $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    public static function set(array &$array, string|int|null $key, mixed $value): array
    {
        if ($key === null) {
            return $array = $value;
        }

        $keys = explode('.', (string) $key);
        $current = &$array;

        foreach ($keys as $i => $segment) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }

        $current[array_shift($keys)] = $value;

        return $array;
    }

    public static function has(array $array, string|array $keys): bool
    {
        $keys = (array) $keys;

        if ($keys === [] || $array === []) {
            return false;
        }

        foreach ($keys as $key) {
            $subArray = $array;

            if (array_key_exists($key, $array)) {
                continue;
            }

            foreach (explode('.', $key) as $segment) {
                if (!is_array($subArray) || !array_key_exists($segment, $subArray)) {
                    return false;
                }

                $subArray = $subArray[$segment];
            }
        }

        return true;
    }

    public static function forget(array &$array, string|array $keys): void
    {
        $keys = (array) $keys;

        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                unset($array[$key]);
                continue;
            }

            $parts = explode('.', $key);
            $current = &$array;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (!isset($current[$part]) || !is_array($current[$part])) {
                    continue 2;
                }

                $current = &$current[$part];
            }

            unset($current[array_shift($parts)]);
        }
    }

    public static function only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    public static function except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }

    public static function first(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            if (empty($array)) {
                return $default;
            }

            return reset($array);
        }

        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    public static function last(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return empty($array) ? $default : end($array);
        }

        return static::first(array_reverse($array, true), $callback, $default);
    }

    public static function flatten(array $array, int $depth = PHP_INT_MAX): array
    {
        $result = [];

        foreach ($array as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } elseif ($depth === 1) {
                $result = array_merge($result, array_values($item));
            } else {
                $result = array_merge($result, static::flatten($item, $depth - 1));
            }
        }

        return $result;
    }

    public static function dot(array $array, string $prepend = ''): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $results = array_merge($results, static::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    public static function undot(array $array): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            static::set($results, $key, $value);
        }

        return $results;
    }

    public static function wrap(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    public static function pluck(array $array, string|array $value, ?string $key = null): array
    {
        $results = [];

        foreach ($array as $item) {
            $itemValue = static::get($item, $value);

            if ($key === null) {
                $results[] = $itemValue;
            } else {
                $results[static::get($item, $key)] = $itemValue;
            }
        }

        return $results;
    }

    public static function where(array $array, callable $callback): array
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    public static function map(array $array, callable $callback): array
    {
        $keys = array_keys($array);
        $values = array_map($callback, $array, $keys);

        return array_combine($keys, $values);
    }

    public static function sortBy(array $array, callable|string $callback): array
    {
        $results = [];

        if (is_string($callback)) {
            $key = $callback;
            $callback = fn($item) => static::get($item, $key);
        }

        foreach ($array as $k => $value) {
            $results[$k] = $callback($value, $k);
        }

        asort($results);

        $sorted = [];
        foreach (array_keys($results) as $key) {
            $sorted[$key] = $array[$key];
        }

        return $sorted;
    }

    public static function groupBy(array $array, callable|string $groupBy): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            $groupKey = is_callable($groupBy)
                ? $groupBy($value, $key)
                : static::get($value, $groupBy);

            $results[$groupKey][] = $value;
        }

        return $results;
    }

    public static function keyBy(array $array, callable|string $keyBy): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            $resolvedKey = is_callable($keyBy)
                ? $keyBy($value, $key)
                : static::get($value, $keyBy);

            $results[$resolvedKey] = $value;
        }

        return $results;
    }
}
