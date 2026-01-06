<?php

declare(strict_types=1);

namespace Forjix\Validation;

use Countable;
use IteratorAggregate;
use JsonSerializable;
use ArrayIterator;

class ValidationErrors implements Countable, IteratorAggregate, JsonSerializable
{
    protected array $errors = [];

    public function __construct(array $errors = [])
    {
        $this->errors = $errors;
    }

    public function has(string $key): bool
    {
        return isset($this->errors[$key]) && count($this->errors[$key]) > 0;
    }

    public function first(?string $key = null): ?string
    {
        if ($key !== null) {
            return $this->errors[$key][0] ?? null;
        }

        foreach ($this->errors as $messages) {
            if (count($messages) > 0) {
                return $messages[0];
            }
        }

        return null;
    }

    public function get(string $key): array
    {
        return $this->errors[$key] ?? [];
    }

    public function all(): array
    {
        $all = [];

        foreach ($this->errors as $messages) {
            $all = array_merge($all, $messages);
        }

        return $all;
    }

    public function keys(): array
    {
        return array_keys($this->errors);
    }

    public function add(string $key, string $message): static
    {
        $this->errors[$key][] = $message;

        return $this;
    }

    public function merge(array $errors): static
    {
        foreach ($errors as $key => $messages) {
            foreach ((array) $messages as $message) {
                $this->add($key, $message);
            }
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function count(): int
    {
        return array_sum(array_map('count', $this->errors));
    }

    public function toArray(): array
    {
        return $this->errors;
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->errors);
    }

    public function __toString(): string
    {
        return implode(', ', $this->all());
    }
}
