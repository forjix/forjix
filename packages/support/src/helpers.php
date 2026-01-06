<?php

declare(strict_types=1);

use Forjix\Support\Arr;
use Forjix\Support\Collection;
use Forjix\Support\Str;

if (!function_exists('collect')) {
    function collect(array $items = []): Collection
    {
        return new Collection($items);
    }
}

if (!function_exists('data_get')) {
    function data_get(mixed $target, string|int|null $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $target;
        }

        if (is_array($target)) {
            return Arr::get($target, $key, $default);
        }

        if (is_object($target)) {
            return $target->{$key} ?? $default;
        }

        return $default;
    }
}

if (!function_exists('data_set')) {
    function data_set(mixed &$target, string|array $key, mixed $value, bool $overwrite = true): mixed
    {
        $segments = is_array($key) ? $key : explode('.', $key);
        $segment = array_shift($segments);

        if ($segment === '*') {
            if (!is_array($target)) {
                $target = [];
            }

            if ($segments) {
                foreach ($target as &$inner) {
                    data_set($inner, $segments, $value, $overwrite);
                }
            } elseif ($overwrite) {
                foreach ($target as &$inner) {
                    $inner = $value;
                }
            }
        } elseif (is_array($target)) {
            if ($segments) {
                if (!isset($target[$segment])) {
                    $target[$segment] = [];
                }

                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || !isset($target[$segment])) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target)) {
            if ($segments) {
                if (!isset($target->{$segment})) {
                    $target->{$segment} = [];
                }

                data_set($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || !isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } else {
            $target = [];

            if ($segments) {
                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite) {
                $target[$segment] = $value;
            }
        }

        return $target;
    }
}

if (!function_exists('value')) {
    function value(mixed $value, mixed ...$args): mixed
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false) {
            return value($default);
        }

        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => $value,
        };
    }
}

if (!function_exists('blank')) {
    function blank(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_numeric($value) || is_bool($value)) {
            return false;
        }

        if ($value instanceof Countable) {
            return count($value) === 0;
        }

        return empty($value);
    }
}

if (!function_exists('filled')) {
    function filled(mixed $value): bool
    {
        return !blank($value);
    }
}

if (!function_exists('class_basename')) {
    function class_basename(string|object $class): string
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}

if (!function_exists('tap')) {
    function tap(mixed $value, ?callable $callback = null): mixed
    {
        if ($callback === null) {
            return new class($value) {
                public function __construct(public mixed $target) {}

                public function __call(string $method, array $parameters): mixed
                {
                    $this->target->{$method}(...$parameters);
                    return $this->target;
                }
            };
        }

        $callback($value);

        return $value;
    }
}

if (!function_exists('transform')) {
    function transform(mixed $value, callable $callback, mixed $default = null): mixed
    {
        if (filled($value)) {
            return $callback($value);
        }

        return value($default);
    }
}

if (!function_exists('retry')) {
    function retry(int $times, callable $callback, int $sleepMs = 0, ?callable $when = null): mixed
    {
        $attempts = 0;
        $backoff = $sleepMs;

        beginning:
        $attempts++;

        try {
            return $callback($attempts);
        } catch (Throwable $e) {
            if ($attempts < $times && ($when === null || $when($e))) {
                if ($backoff > 0) {
                    usleep($backoff * 1000);
                }

                goto beginning;
            }

            throw $e;
        }
    }
}

if (!function_exists('rescue')) {
    function rescue(callable $callback, mixed $rescue = null, bool $report = true): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            return value($rescue, $e);
        }
    }
}

if (!function_exists('throw_if')) {
    function throw_if(mixed $condition, Throwable|string $exception = 'RuntimeException', mixed ...$parameters): mixed
    {
        if ($condition) {
            if (is_string($exception)) {
                $exception = new $exception(...$parameters);
            }

            throw $exception;
        }

        return $condition;
    }
}

if (!function_exists('throw_unless')) {
    function throw_unless(mixed $condition, Throwable|string $exception = 'RuntimeException', mixed ...$parameters): mixed
    {
        throw_if(!$condition, $exception, ...$parameters);

        return $condition;
    }
}

if (!function_exists('windows_os')) {
    function windows_os(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }
}

if (!function_exists('str')) {
    function str(?string $string = null): Str|string
    {
        if ($string === null) {
            return new Str();
        }

        return $string;
    }
}

if (!function_exists('head')) {
    function head(array $array): mixed
    {
        return reset($array);
    }
}

if (!function_exists('last')) {
    function last(array $array): mixed
    {
        return end($array);
    }
}

if (!function_exists('now')) {
    function now(string $timezone = null): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $timezone ? new DateTimeZone($timezone) : null);
    }
}
