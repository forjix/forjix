<?php

declare(strict_types=1);

namespace Forjix\Core\Container;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;

class Container implements ContainerInterface
{
    protected static ?Container $instance = null;

    protected array $bindings = [];
    protected array $instances = [];
    protected array $aliases = [];
    protected array $resolved = [];

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public static function setInstance(?Container $container = null): ?Container
    {
        return static::$instance = $container;
    }

    public function bind(string $abstract, callable|string|null $concrete = null, bool $shared = false): void
    {
        $this->dropStaleInstances($abstract);

        if ($concrete === null) {
            $concrete = $abstract;
        }

        if (!$concrete instanceof Closure) {
            if (is_string($concrete)) {
                $concrete = $this->getClosure($abstract, $concrete);
            }
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];
    }

    public function singleton(string $abstract, callable|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function instance(string $abstract, mixed $instance): mixed
    {
        $this->removeAbstractAlias($abstract);

        unset($this->aliases[$abstract]);

        $this->instances[$abstract] = $instance;

        return $instance;
    }

    public function alias(string $abstract, string $alias): void
    {
        if ($alias === $abstract) {
            throw new RuntimeException("[$abstract] is aliased to itself.");
        }

        $this->aliases[$alias] = $abstract;
    }

    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract])
            || isset($this->instances[$abstract])
            || $this->isAlias($abstract);
    }

    public function resolved(string $abstract): bool
    {
        if ($this->isAlias($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        return isset($this->resolved[$abstract]) || isset($this->instances[$abstract]);
    }

    public function isAlias(string $name): bool
    {
        return isset($this->aliases[$name]);
    }

    public function getAlias(string $abstract): string
    {
        return isset($this->aliases[$abstract])
            ? $this->getAlias($this->aliases[$abstract])
            : $abstract;
    }

    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters);
    }

    public function get(string $id): mixed
    {
        if ($this->has($id)) {
            return $this->resolve($id);
        }

        throw new NotFoundException("Entry [{$id}] not found in container.");
    }

    public function has(string $id): bool
    {
        return $this->bound($id);
    }

    protected function resolve(string $abstract, array $parameters = []): mixed
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->instances[$abstract]) && empty($parameters)) {
            return $this->instances[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        $object = $this->build($concrete, $parameters);

        if ($this->isShared($abstract) && empty($parameters)) {
            $this->instances[$abstract] = $object;
        }

        $this->resolved[$abstract] = true;

        return $object;
    }

    protected function getConcrete(string $abstract): mixed
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    protected function build(mixed $concrete, array $parameters = []): mixed
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        if (!is_string($concrete)) {
            return $concrete;
        }

        if (!class_exists($concrete)) {
            throw new NotFoundException("Class [{$concrete}] does not exist.");
        }

        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new RuntimeException("Class [{$concrete}] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        $dependencies = $this->resolveDependencies(
            $constructor->getParameters(),
            $parameters
        );

        return $reflector->newInstanceArgs($dependencies);
    }

    protected function resolveDependencies(array $dependencies, array $parameters = []): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            if (array_key_exists($dependency->getName(), $parameters)) {
                $results[] = $parameters[$dependency->getName()];
                continue;
            }

            $result = $this->resolveDependency($dependency);

            if ($result === null && $dependency->isDefaultValueAvailable()) {
                $result = $dependency->getDefaultValue();
            }

            $results[] = $result;
        }

        return $results;
    }

    protected function resolveDependency(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            if ($parameter->allowsNull()) {
                return null;
            }

            throw new RuntimeException(
                "Unresolvable dependency [{$parameter->getName()}] in class {$parameter->getDeclaringClass()->getName()}"
            );
        }

        return $this->make($type->getName());
    }

    public function call(callable|string $callback, array $parameters = [], ?string $defaultMethod = null): mixed
    {
        if (is_string($callback) && str_contains($callback, '@')) {
            $callback = explode('@', $callback);
        }

        if (is_string($callback) && $defaultMethod !== null) {
            $callback = [$this->make($callback), $defaultMethod];
        }

        if (is_array($callback)) {
            [$class, $method] = $callback;

            if (is_string($class)) {
                $class = $this->make($class);
            }

            $reflector = new ReflectionMethod($class, $method);
            $dependencies = $this->resolveDependencies($reflector->getParameters(), $parameters);

            return $reflector->invokeArgs($class, $dependencies);
        }

        if (is_object($callback) && method_exists($callback, '__invoke')) {
            $reflector = new ReflectionMethod($callback, '__invoke');
            $dependencies = $this->resolveDependencies($reflector->getParameters(), $parameters);

            return $callback(...$dependencies);
        }

        $reflector = new ReflectionFunction($callback);
        $dependencies = $this->resolveDependencies($reflector->getParameters(), $parameters);

        return $callback(...$dependencies);
    }

    protected function getClosure(string $abstract, string $concrete): Closure
    {
        return function (Container $container, array $parameters = []) use ($abstract, $concrete) {
            if ($abstract === $concrete) {
                return $container->build($concrete, $parameters);
            }

            return $container->make($concrete, $parameters);
        };
    }

    protected function isShared(string $abstract): bool
    {
        return isset($this->instances[$abstract])
            || (isset($this->bindings[$abstract]['shared']) && $this->bindings[$abstract]['shared'] === true);
    }

    protected function dropStaleInstances(string $abstract): void
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
    }

    protected function removeAbstractAlias(string $searched): void
    {
        if (!isset($this->aliases[$searched])) {
            return;
        }

        foreach ($this->aliases as $alias => $abstract) {
            if ($abstract === $searched) {
                unset($this->aliases[$alias]);
            }
        }
    }

    public function flush(): void
    {
        $this->aliases = [];
        $this->resolved = [];
        $this->bindings = [];
        $this->instances = [];
    }

    public function forgetInstance(string $abstract): void
    {
        unset($this->instances[$abstract]);
    }

    public function forgetInstances(): void
    {
        $this->instances = [];
    }
}
