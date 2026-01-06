<?php

declare(strict_types=1);

namespace Forjix\Database\Schema;

use Closure;
use Forjix\Database\Connection;

class Schema
{
    protected Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function create(string $table, Closure $callback): void
    {
        $blueprint = $this->createBlueprint($table);

        $callback($blueprint);

        $this->build($blueprint);
    }

    public function table(string $table, Closure $callback): void
    {
        $blueprint = $this->createBlueprint($table);

        $callback($blueprint);

        $this->buildModify($blueprint);
    }

    public function drop(string $table): void
    {
        $sql = "drop table {$this->connection->prefixTable($table)}";

        $this->connection->statement($sql);
    }

    public function dropIfExists(string $table): void
    {
        $sql = "drop table if exists {$this->connection->prefixTable($table)}";

        $this->connection->statement($sql);
    }

    public function rename(string $from, string $to): void
    {
        $sql = sprintf(
            'alter table %s rename to %s',
            $this->connection->prefixTable($from),
            $this->connection->prefixTable($to)
        );

        $this->connection->statement($sql);
    }

    public function hasTable(string $table): bool
    {
        $driver = $this->connection->getDriverName();

        $sql = match ($driver) {
            'mysql' => "select * from information_schema.tables where table_schema = database() and table_name = ?",
            'pgsql' => "select * from information_schema.tables where table_catalog = current_database() and table_name = ?",
            'sqlite' => "select * from sqlite_master where type = 'table' and name = ?",
            default => throw new \RuntimeException("Unsupported driver: {$driver}"),
        };

        $result = $this->connection->select($sql, [$this->connection->prefixTable($table)]);

        return count($result) > 0;
    }

    public function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->getColumnListing($table), true);
    }

    public function hasColumns(string $table, array $columns): bool
    {
        $tableColumns = $this->getColumnListing($table);

        foreach ($columns as $column) {
            if (!in_array($column, $tableColumns, true)) {
                return false;
            }
        }

        return true;
    }

    public function getColumnListing(string $table): array
    {
        $driver = $this->connection->getDriverName();
        $prefixedTable = $this->connection->prefixTable($table);

        $sql = match ($driver) {
            'mysql' => "select column_name from information_schema.columns where table_schema = database() and table_name = ?",
            'pgsql' => "select column_name from information_schema.columns where table_catalog = current_database() and table_name = ?",
            'sqlite' => "pragma table_info({$prefixedTable})",
            default => throw new \RuntimeException("Unsupported driver: {$driver}"),
        };

        if ($driver === 'sqlite') {
            $results = $this->connection->select($sql);
            return array_map(fn($row) => $row->name, $results);
        }

        $results = $this->connection->select($sql, [$prefixedTable]);

        return array_map(fn($row) => $row->column_name ?? $row->COLUMN_NAME, $results);
    }

    public function getTables(): array
    {
        $driver = $this->connection->getDriverName();

        $sql = match ($driver) {
            'mysql' => "show tables",
            'pgsql' => "select table_name from information_schema.tables where table_schema = 'public'",
            'sqlite' => "select name from sqlite_master where type = 'table' and name not like 'sqlite_%'",
            default => throw new \RuntimeException("Unsupported driver: {$driver}"),
        };

        $results = $this->connection->select($sql);

        return array_map(fn($row) => array_values((array) $row)[0], $results);
    }

    protected function createBlueprint(string $table): Blueprint
    {
        return new Blueprint($table);
    }

    protected function build(Blueprint $blueprint): void
    {
        $driver = $this->connection->getDriverName();
        $statements = $blueprint->build($driver);

        foreach ($statements as $statement) {
            $this->connection->statement($statement);
        }

        // Build foreign key constraints
        foreach ($blueprint->getCommands() as $command) {
            if ($command['type'] === 'foreign') {
                $sql = $command['definition']->toSql($blueprint->getTable());
                $this->connection->statement($sql);
            }
        }
    }

    protected function buildModify(Blueprint $blueprint): void
    {
        $driver = $this->connection->getDriverName();
        $table = $this->connection->prefixTable($blueprint->getTable());

        foreach ($blueprint->getColumns() as $column) {
            $sql = "alter table {$table} add column " . $this->compileColumn($column, $driver);
            $this->connection->statement($sql);
        }

        foreach ($blueprint->getCommands() as $command) {
            $sql = $this->compileCommand($command, $table, $driver);
            if ($sql) {
                $this->connection->statement($sql);
            }
        }
    }

    protected function compileColumn($column, string $driver): string
    {
        $blueprint = new Blueprint('temp');
        $reflection = new \ReflectionMethod($blueprint, 'compileColumn');
        $reflection->setAccessible(true);

        return $reflection->invoke($blueprint, $column, $driver);
    }

    protected function compileCommand(array $command, string $table, string $driver): ?string
    {
        return match ($command['type']) {
            'dropColumn' => $this->compileDropColumn($command, $table, $driver),
            'renameColumn' => "alter table {$table} rename column {$command['from']} to {$command['to']}",
            'index' => "create index {$this->getIndexName($command, $table)} on {$table} (" . implode(', ', $command['columns']) . ')',
            'unique' => "create unique index {$this->getIndexName($command, $table, 'unique')} on {$table} (" . implode(', ', $command['columns']) . ')',
            'dropIndex' => "drop index {$command['name']} on {$table}",
            'dropUnique' => "drop index {$command['name']} on {$table}",
            'foreign' => $command['definition']->toSql($table),
            default => null,
        };
    }

    protected function compileDropColumn(array $command, string $table, string $driver): string
    {
        $columns = implode(', ', array_map(fn($col) => "drop column {$col}", $command['columns']));

        return "alter table {$table} {$columns}";
    }

    protected function getIndexName(array $command, string $table, string $type = 'index'): string
    {
        if ($command['name']) {
            return $command['name'];
        }

        return $table . '_' . implode('_', $command['columns']) . '_' . $type;
    }
}
