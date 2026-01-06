<?php

declare(strict_types=1);

namespace Forjix\Core\Container;

use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface
{
    public function bind(string $abstract, callable|string|null $concrete = null, bool $shared = false): void;

    public function singleton(string $abstract, callable|string|null $concrete = null): void;

    public function instance(string $abstract, mixed $instance): mixed;

    public function make(string $abstract, array $parameters = []): mixed;

    public function alias(string $abstract, string $alias): void;

    public function bound(string $abstract): bool;
}
