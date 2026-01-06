<?php

declare(strict_types=1);

namespace Forjix\Database;

use InvalidArgumentException;

class DatabaseManager
{
    protected array $connections = [];
    protected array $config;
    protected ?string $defaultConnection = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->defaultConnection = $config['default'] ?? 'default';
    }

    public function connection(?string $name = null): Connection
    {
        $name = $name ?? $this->defaultConnection;

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->makeConnection($name);
        }

        return $this->connections[$name];
    }

    protected function makeConnection(string $name): Connection
    {
        $config = $this->getConfig($name);

        if ($config === null) {
            throw new InvalidArgumentException("Database connection [{$name}] not configured.");
        }

        return new Connection($config);
    }

    protected function getConfig(string $name): ?array
    {
        return $this->config['connections'][$name] ?? null;
    }

    public function purge(?string $name = null): void
    {
        $name = $name ?? $this->defaultConnection;

        if (isset($this->connections[$name])) {
            $this->connections[$name]->disconnect();
            unset($this->connections[$name]);
        }
    }

    public function disconnect(?string $name = null): void
    {
        $this->purge($name);
    }

    public function reconnect(?string $name = null): Connection
    {
        $this->purge($name);

        return $this->connection($name);
    }

    public function getDefaultConnection(): string
    {
        return $this->defaultConnection;
    }

    public function setDefaultConnection(string $name): void
    {
        $this->defaultConnection = $name;
    }

    public function getConnections(): array
    {
        return $this->connections;
    }

    public function __call(string $method, array $parameters): mixed
    {
        return $this->connection()->{$method}(...$parameters);
    }
}
