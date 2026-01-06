<?php

declare(strict_types=1);

namespace Forjix\Validation\Rules;

interface Rule
{
    public function passes(string $attribute, mixed $value): bool;

    public function message(): string;
}
