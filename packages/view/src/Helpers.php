<?php

declare(strict_types=1);

namespace Forjix\View;

class Helpers
{
    public static function classAttribute(array $classes): string
    {
        $result = [];

        foreach ($classes as $key => $value) {
            if (is_numeric($key)) {
                $result[] = $value;
            } elseif ($value) {
                $result[] = $key;
            }
        }

        $classes = implode(' ', $result);

        return $classes !== '' ? "class=\"{$classes}\"" : '';
    }

    public static function styleAttribute(array $styles): string
    {
        $result = [];

        foreach ($styles as $key => $value) {
            if (is_numeric($key)) {
                $result[] = $value;
            } elseif ($value !== false && $value !== null) {
                $result[] = "{$key}: {$value}";
            }
        }

        $styles = implode('; ', $result);

        return $styles !== '' ? "style=\"{$styles}\"" : '';
    }
}
