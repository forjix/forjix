<?php

declare(strict_types=1);

namespace Forjix\Validation\Rules;

class Password implements Rule
{
    protected int $min = 8;
    protected bool $mixedCase = false;
    protected bool $letters = false;
    protected bool $numbers = false;
    protected bool $symbols = false;
    protected bool $uncompromised = false;
    protected array $messages = [];

    public static function min(int $size): static
    {
        $rule = new static();
        $rule->min = $size;

        return $rule;
    }

    public static function defaults(): static
    {
        return (new static())
            ->min(8)
            ->mixedCase()
            ->numbers();
    }

    public function min(int $size): static
    {
        $this->min = $size;

        return $this;
    }

    public function mixedCase(): static
    {
        $this->mixedCase = true;

        return $this;
    }

    public function letters(): static
    {
        $this->letters = true;

        return $this;
    }

    public function numbers(): static
    {
        $this->numbers = true;

        return $this;
    }

    public function symbols(): static
    {
        $this->symbols = true;

        return $this;
    }

    public function uncompromised(): static
    {
        $this->uncompromised = true;

        return $this;
    }

    public function passes(string $attribute, mixed $value): bool
    {
        $this->messages = [];

        if (!is_string($value)) {
            $this->messages[] = 'The password must be a string.';
            return false;
        }

        if (mb_strlen($value) < $this->min) {
            $this->messages[] = "The password must be at least {$this->min} characters.";
            return false;
        }

        if ($this->mixedCase && !preg_match('/(\p{Ll}+.*\p{Lu})|(\p{Lu}+.*\p{Ll})/u', $value)) {
            $this->messages[] = 'The password must contain at least one uppercase and one lowercase letter.';
            return false;
        }

        if ($this->letters && !preg_match('/\pL/u', $value)) {
            $this->messages[] = 'The password must contain at least one letter.';
            return false;
        }

        if ($this->numbers && !preg_match('/\pN/u', $value)) {
            $this->messages[] = 'The password must contain at least one number.';
            return false;
        }

        if ($this->symbols && !preg_match('/[^\pL\pN]/u', $value)) {
            $this->messages[] = 'The password must contain at least one symbol.';
            return false;
        }

        return true;
    }

    public function message(): string
    {
        return implode(' ', $this->messages);
    }
}
