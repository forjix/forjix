<?php

declare(strict_types=1);

namespace Forjix\Support;

use Closure;

class Pipeline
{
    protected mixed $passable = null;
    protected array $pipes = [];
    protected string $method = 'handle';

    public function __construct(protected ?object $container = null)
    {
    }

    public function send(mixed $passable): static
    {
        $this->passable = $passable;

        return $this;
    }

    public function through(array|mixed $pipes): static
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();

        return $this;
    }

    public function pipe(array|mixed $pipes): static
    {
        array_push($this->pipes, ...(is_array($pipes) ? $pipes : func_get_args()));

        return $this;
    }

    public function via(string $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function then(Closure $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    public function thenReturn(): mixed
    {
        return $this->then(fn($passable) => $passable);
    }

    protected function prepareDestination(Closure $destination): Closure
    {
        return function ($passable) use ($destination) {
            return $destination($passable);
        };
    }

    protected function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if (is_callable($pipe)) {
                    return $pipe($passable, $stack);
                }

                if (is_string($pipe)) {
                    [$name, $parameters] = $this->parsePipeString($pipe);

                    $pipe = $this->container
                        ? $this->container->get($name)
                        : new $name();

                    $parameters = array_merge([$passable, $stack], $parameters);
                } else {
                    $parameters = [$passable, $stack];
                }

                $response = method_exists($pipe, $this->method)
                    ? $pipe->{$this->method}(...$parameters)
                    : $pipe(...$parameters);

                return $response;
            };
        };
    }

    protected function parsePipeString(string $pipe): array
    {
        [$name, $parameters] = array_pad(explode(':', $pipe, 2), 2, []);

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return [$name, $parameters];
    }
}
