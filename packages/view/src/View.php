<?php

declare(strict_types=1);

namespace Forjix\View;

class View implements \Stringable
{
    protected Engine $engine;
    protected string $view;
    protected array $data = [];

    public function __construct(Engine $engine, string $view, array $data = [])
    {
        $this->engine = $engine;
        $this->view = $view;
        $this->data = $data;
    }

    public function render(): string
    {
        return $this->engine->renderContents($this->view, $this->data);
    }

    public function with(string|array $key, mixed $value = null): static
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getName(): string
    {
        return $this->view;
    }

    public function getEngine(): Engine
    {
        return $this->engine;
    }

    public function __toString(): string
    {
        return $this->render();
    }

    public function __get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function __unset(string $key): void
    {
        unset($this->data[$key]);
    }
}
