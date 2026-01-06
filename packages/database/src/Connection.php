<?php

declare(strict_types=1);

namespace Forjix\Database;

use PDO;
use PDOException;
use PDOStatement;

class Connection
{
    protected ?PDO $pdo = null;
    protected array $config;
    protected string $tablePrefix = '';
    protected array $queryLog = [];
    protected bool $loggingQueries = false;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->tablePrefix = $config['prefix'] ?? '';
    }

    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = $this->createConnection();
        }

        return $this->pdo;
    }

    protected function createConnection(): PDO
    {
        $driver = $this->config['driver'] ?? 'mysql';

        $dsn = match ($driver) {
            'mysql' => $this->getMysqlDsn(),
            'pgsql' => $this->getPostgresDsn(),
            'sqlite' => $this->getSqliteDsn(),
            default => throw new PDOException("Unsupported driver: {$driver}"),
        };

        $pdo = new PDO(
            $dsn,
            $this->config['username'] ?? null,
            $this->config['password'] ?? null,
            $this->getOptions()
        );

        return $pdo;
    }

    protected function getMysqlDsn(): string
    {
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = $this->config['port'] ?? 3306;
        $database = $this->config['database'] ?? '';
        $charset = $this->config['charset'] ?? 'utf8mb4';

        return "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
    }

    protected function getPostgresDsn(): string
    {
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = $this->config['port'] ?? 5432;
        $database = $this->config['database'] ?? '';

        return "pgsql:host={$host};port={$port};dbname={$database}";
    }

    protected function getSqliteDsn(): string
    {
        $database = $this->config['database'] ?? ':memory:';

        return "sqlite:{$database}";
    }

    protected function getOptions(): array
    {
        $default = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return array_replace($default, $this->config['options'] ?? []);
    }

    public function table(string $table): QueryBuilder
    {
        return (new QueryBuilder($this))->from($table);
    }

    public function query(string $sql, array $bindings = []): PDOStatement
    {
        $start = microtime(true);

        $statement = $this->getPdo()->prepare($sql);
        $statement->execute($bindings);

        if ($this->loggingQueries) {
            $this->queryLog[] = [
                'query' => $sql,
                'bindings' => $bindings,
                'time' => microtime(true) - $start,
            ];
        }

        return $statement;
    }

    public function select(string $sql, array $bindings = []): array
    {
        return $this->query($sql, $bindings)->fetchAll();
    }

    public function selectOne(string $sql, array $bindings = []): ?object
    {
        $result = $this->query($sql, $bindings)->fetch();

        return $result === false ? null : $result;
    }

    public function insert(string $sql, array $bindings = []): bool
    {
        return $this->query($sql, $bindings)->rowCount() > 0;
    }

    public function update(string $sql, array $bindings = []): int
    {
        return $this->query($sql, $bindings)->rowCount();
    }

    public function delete(string $sql, array $bindings = []): int
    {
        return $this->query($sql, $bindings)->rowCount();
    }

    public function statement(string $sql, array $bindings = []): bool
    {
        return $this->query($sql, $bindings) !== false;
    }

    public function lastInsertId(?string $name = null): string|false
    {
        return $this->getPdo()->lastInsertId($name);
    }

    public function beginTransaction(): bool
    {
        return $this->getPdo()->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->getPdo()->commit();
    }

    public function rollBack(): bool
    {
        return $this->getPdo()->rollBack();
    }

    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();

            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    public function prefixTable(string $table): string
    {
        return $this->tablePrefix . $table;
    }

    public function enableQueryLog(): void
    {
        $this->loggingQueries = true;
    }

    public function disableQueryLog(): void
    {
        $this->loggingQueries = false;
    }

    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    public function flushQueryLog(): void
    {
        $this->queryLog = [];
    }

    public function disconnect(): void
    {
        $this->pdo = null;
    }

    public function reconnect(): void
    {
        $this->disconnect();
        $this->getPdo();
    }

    public function getConfig(string $key = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }

        return $this->config[$key] ?? null;
    }

    public function getDriverName(): string
    {
        return $this->config['driver'] ?? 'mysql';
    }
}
