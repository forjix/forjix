<?php

declare(strict_types=1);

namespace Forjix\Support;

class Str
{
    protected static array $snakeCache = [];
    protected static array $camelCache = [];
    protected static array $studlyCache = [];

    public static function after(string $subject, string $search): string
    {
        return $search === '' ? $subject : array_reverse(explode($search, $subject, 2))[0];
    }

    public static function afterLast(string $subject, string $search): string
    {
        if ($search === '') {
            return $subject;
        }

        $position = strrpos($subject, $search);

        if ($position === false) {
            return $subject;
        }

        return substr($subject, $position + strlen($search));
    }

    public static function before(string $subject, string $search): string
    {
        return $search === '' ? $subject : explode($search, $subject)[0];
    }

    public static function beforeLast(string $subject, string $search): string
    {
        if ($search === '') {
            return $subject;
        }

        $position = strrpos($subject, $search);

        if ($position === false) {
            return $subject;
        }

        return substr($subject, 0, $position);
    }

    public static function between(string $subject, string $from, string $to): string
    {
        if ($from === '' || $to === '') {
            return $subject;
        }

        return static::beforeLast(static::after($subject, $from), $to);
    }

    public static function camel(string $value): string
    {
        if (isset(static::$camelCache[$value])) {
            return static::$camelCache[$value];
        }

        return static::$camelCache[$value] = lcfirst(static::studly($value));
    }

    public static function contains(string $haystack, string|array $needles, bool $ignoreCase = false): bool
    {
        if ($ignoreCase) {
            $haystack = mb_strtolower($haystack);
        }

        foreach ((array) $needles as $needle) {
            if ($ignoreCase) {
                $needle = mb_strtolower($needle);
            }

            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    public static function containsAll(string $haystack, array $needles, bool $ignoreCase = false): bool
    {
        foreach ($needles as $needle) {
            if (!static::contains($haystack, $needle, $ignoreCase)) {
                return false;
            }
        }

        return true;
    }

    public static function endsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_ends_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    public static function startsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_starts_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    public static function finish(string $value, string $cap): string
    {
        return preg_replace('/(?:' . preg_quote($cap, '/') . ')+$/u', '', $value) . $cap;
    }

    public static function start(string $value, string $prefix): string
    {
        return $prefix . preg_replace('/^(?:' . preg_quote($prefix, '/') . ')+/u', '', $value);
    }

    public static function kebab(string $value): string
    {
        return static::snake($value, '-');
    }

    public static function length(string $value): int
    {
        return mb_strlen($value);
    }

    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return mb_substr($value, 0, $limit) . $end;
    }

    public static function lower(string $value): string
    {
        return mb_strtolower($value);
    }

    public static function upper(string $value): string
    {
        return mb_strtoupper($value);
    }

    public static function title(string $value): string
    {
        return mb_convert_case($value, MB_CASE_TITLE);
    }

    public static function headline(string $value): string
    {
        $parts = explode(' ', str_replace(['-', '_'], ' ', static::snake($value, ' ')));

        return implode(' ', array_map(fn($part) => ucfirst($part), $parts));
    }

    public static function slug(string $title, string $separator = '-'): string
    {
        $title = preg_replace('/[^\pL\pN\s]+/u', '', mb_strtolower($title));
        $title = preg_replace('/[\s]+/u', $separator, trim($title));

        return $title;
    }

    public static function snake(string $value, string $delimiter = '_'): string
    {
        $key = $value;

        if (isset(static::$snakeCache[$key][$delimiter])) {
            return static::$snakeCache[$key][$delimiter];
        }

        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));
            $value = mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
        }

        return static::$snakeCache[$key][$delimiter] = $value;
    }

    public static function studly(string $value): string
    {
        $key = $value;

        if (isset(static::$studlyCache[$key])) {
            return static::$studlyCache[$key];
        }

        $words = explode(' ', str_replace(['-', '_'], ' ', $value));

        $studly = array_reduce($words, fn($carry, $word) => $carry . ucfirst($word));

        return static::$studlyCache[$key] = $studly;
    }

    public static function substr(string $string, int $start, ?int $length = null): string
    {
        return mb_substr($string, $start, $length);
    }

    public static function ucfirst(string $string): string
    {
        return static::upper(static::substr($string, 0, 1)) . static::substr($string, 1);
    }

    public static function lcfirst(string $string): string
    {
        return static::lower(static::substr($string, 0, 1)) . static::substr($string, 1);
    }

    public static function replace(string|array $search, string|array $replace, string $subject): string
    {
        return str_replace($search, $replace, $subject);
    }

    public static function replaceFirst(string $search, string $replace, string $subject): string
    {
        if ($search === '') {
            return $subject;
        }

        $position = strpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }

    public static function replaceLast(string $search, string $replace, string $subject): string
    {
        if ($search === '') {
            return $subject;
        }

        $position = strrpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }

    public static function random(int $length = 16): string
    {
        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;
            $bytes = random_bytes($size);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }

    public static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    public static function words(string $value, int $words = 100, string $end = '...'): string
    {
        preg_match('/^\s*+(?:\S++\s*+){1,' . $words . '}/u', $value, $matches);

        if (!isset($matches[0]) || static::length($value) === static::length($matches[0])) {
            return $value;
        }

        return rtrim($matches[0]) . $end;
    }

    public static function mask(string $string, string $character, int $index, ?int $length = null): string
    {
        if ($character === '') {
            return $string;
        }

        $stringLength = mb_strlen($string);

        if ($index < 0) {
            $index = max(0, $stringLength + $index);
        }

        if ($length === null) {
            $length = $stringLength;
        }

        if ($length < 0) {
            $length = max(0, $stringLength - $index + $length);
        }

        $start = mb_substr($string, 0, $index);
        $end = mb_substr($string, $index + $length);
        $masked = str_repeat($character, min($length, $stringLength - $index));

        return $start . $masked . $end;
    }

    public static function padLeft(string $value, int $length, string $pad = ' '): string
    {
        return str_pad($value, $length, $pad, STR_PAD_LEFT);
    }

    public static function padRight(string $value, int $length, string $pad = ' '): string
    {
        return str_pad($value, $length, $pad, STR_PAD_RIGHT);
    }

    public static function padBoth(string $value, int $length, string $pad = ' '): string
    {
        return str_pad($value, $length, $pad, STR_PAD_BOTH);
    }

    public static function is(string|array $pattern, string $value): bool
    {
        foreach ((array) $pattern as $p) {
            if ($p === $value) {
                return true;
            }

            $p = preg_quote($p, '#');
            $p = str_replace('\*', '.*', $p);

            if (preg_match('#^' . $p . '\z#u', $value) === 1) {
                return true;
            }
        }

        return false;
    }

    public static function isEmpty(string $value): bool
    {
        return trim($value) === '';
    }

    public static function isNotEmpty(string $value): bool
    {
        return !static::isEmpty($value);
    }

    public static function squish(string $value): string
    {
        return preg_replace('/[\s\t\n\r]+/', ' ', trim($value));
    }
}
