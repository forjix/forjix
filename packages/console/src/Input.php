<?php

declare(strict_types=1);

namespace Forjix\Console;

class Input
{
    protected array $arguments = [];
    protected array $options = [];
    protected array $rawArguments = [];

    public function __construct(array $argv = [])
    {
        $this->rawArguments = $argv;
        $this->parse($argv);
    }

    protected function parse(array $argv): void
    {
        // Remove script name
        array_shift($argv);

        $argumentIndex = 0;

        while (count($argv) > 0) {
            $token = array_shift($argv);

            if (str_starts_with($token, '--')) {
                $this->parseLongOption($token, $argv);
            } elseif (str_starts_with($token, '-')) {
                $this->parseShortOption($token, $argv);
            } else {
                $this->arguments[$argumentIndex] = $token;
                $argumentIndex++;
            }
        }
    }

    protected function parseLongOption(string $token, array &$argv): void
    {
        $token = substr($token, 2);

        if (str_contains($token, '=')) {
            [$name, $value] = explode('=', $token, 2);
            $this->options[$name] = $value;
        } else {
            // Check if next token is the value
            if (!empty($argv) && !str_starts_with($argv[0], '-')) {
                $this->options[$token] = array_shift($argv);
            } else {
                $this->options[$token] = true;
            }
        }
    }

    protected function parseShortOption(string $token, array &$argv): void
    {
        $token = substr($token, 1);

        // Handle combined short options like -abc
        if (strlen($token) > 1 && !str_contains($token, '=')) {
            foreach (str_split($token) as $char) {
                $this->options[$char] = true;
            }
        } elseif (str_contains($token, '=')) {
            [$name, $value] = explode('=', $token, 2);
            $this->options[$name] = $value;
        } else {
            // Check if next token is the value
            if (!empty($argv) && !str_starts_with($argv[0], '-')) {
                $this->options[$token] = array_shift($argv);
            } else {
                $this->options[$token] = true;
            }
        }
    }

    public function argument(string|int $key, mixed $default = null): mixed
    {
        return $this->arguments[$key] ?? $default;
    }

    public function arguments(): array
    {
        return $this->arguments;
    }

    public function hasArgument(string|int $key): bool
    {
        return isset($this->arguments[$key]);
    }

    public function option(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    public function options(): array
    {
        return $this->options;
    }

    public function hasOption(string $key): bool
    {
        return isset($this->options[$key]);
    }

    public function setArgument(string|int $key, mixed $value): void
    {
        $this->arguments[$key] = $value;
    }

    public function setOption(string $key, mixed $value): void
    {
        $this->options[$key] = $value;
    }

    public function getCommand(): ?string
    {
        return $this->arguments[0] ?? null;
    }

    public function getRawArguments(): array
    {
        return $this->rawArguments;
    }
}
