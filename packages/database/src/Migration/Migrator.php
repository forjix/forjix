<?php

declare(strict_types=1);

namespace Forjix\Database\Migration;

use Forjix\Database\Connection;
use Forjix\Database\Schema\Schema;
use Forjix\Support\Collection;

class Migrator
{
    protected Connection $connection;
    protected Schema $schema;
    protected string $table = 'migrations';
    protected array $paths = [];

    public function __construct(Connection $connection, array $paths = [])
    {
        $this->connection = $connection;
        $this->schema = new Schema($connection);
        $this->paths = $paths;
    }

    public function run(): array
    {
        $this->ensureMigrationTableExists();

        $files = $this->getMigrationFiles();
        $ran = $this->getRan();

        $migrations = (new Collection($files))
            ->reject(fn($file) => in_array($this->getMigrationName($file), $ran, true))
            ->values()
            ->all();

        $batch = $this->getLastBatchNumber() + 1;

        foreach ($migrations as $file) {
            $this->runUp($file, $batch);
        }

        return $migrations;
    }

    public function rollback(int $steps = 1): array
    {
        $migrations = $this->getMigrationsForRollback($steps);
        $rolledBack = [];

        foreach ($migrations as $migration) {
            $this->runDown($migration);
            $rolledBack[] = $migration->migration;
        }

        return $rolledBack;
    }

    public function reset(): array
    {
        $migrations = $this->connection
            ->table($this->table)
            ->orderBy('batch', 'desc')
            ->orderBy('migration', 'desc')
            ->get();

        $rolledBack = [];

        foreach ($migrations as $migration) {
            $this->runDown($migration);
            $rolledBack[] = $migration->migration;
        }

        return $rolledBack;
    }

    public function refresh(): void
    {
        $this->reset();
        $this->run();
    }

    protected function runUp(string $file, int $batch): void
    {
        $migration = $this->resolve($file);
        $name = $this->getMigrationName($file);

        $migration->setConnection($this->connection);
        $migration->up();

        $this->connection->table($this->table)->insert([
            'migration' => $name,
            'batch' => $batch,
        ]);
    }

    protected function runDown(object $migration): void
    {
        $file = $this->findMigrationFile($migration->migration);

        if ($file) {
            $instance = $this->resolve($file);
            $instance->setConnection($this->connection);
            $instance->down();
        }

        $this->connection->table($this->table)
            ->where('migration', $migration->migration)
            ->delete();
    }

    protected function resolve(string $file): Migration
    {
        $class = $this->getMigrationClass($file);

        require_once $file;

        return new $class();
    }

    protected function getMigrationClass(string $file): string
    {
        $name = $this->getMigrationName($file);

        // Remove timestamp prefix and convert to StudlyCase
        $name = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $name);

        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }

    protected function getMigrationName(string $file): string
    {
        return basename($file, '.php');
    }

    protected function getMigrationFiles(): array
    {
        $files = [];

        foreach ($this->paths as $path) {
            if (is_dir($path)) {
                $files = array_merge($files, glob($path . '/*.php'));
            }
        }

        sort($files);

        return $files;
    }

    protected function findMigrationFile(string $name): ?string
    {
        foreach ($this->paths as $path) {
            $file = $path . '/' . $name . '.php';
            if (file_exists($file)) {
                return $file;
            }
        }

        return null;
    }

    protected function getRan(): array
    {
        if (!$this->schema->hasTable($this->table)) {
            return [];
        }

        return $this->connection
            ->table($this->table)
            ->orderBy('batch')
            ->orderBy('migration')
            ->pluck('migration')
            ->all();
    }

    protected function getLastBatchNumber(): int
    {
        if (!$this->schema->hasTable($this->table)) {
            return 0;
        }

        return (int) $this->connection->table($this->table)->max('batch');
    }

    protected function getMigrationsForRollback(int $steps): Collection
    {
        return $this->connection
            ->table($this->table)
            ->orderBy('batch', 'desc')
            ->orderBy('migration', 'desc')
            ->limit($steps)
            ->get();
    }

    protected function ensureMigrationTableExists(): void
    {
        if ($this->schema->hasTable($this->table)) {
            return;
        }

        $this->schema->create($this->table, function ($table) {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
        });
    }

    public function addPath(string $path): static
    {
        $this->paths[] = $path;

        return $this;
    }

    public function getPaths(): array
    {
        return $this->paths;
    }

    public function setTable(string $table): static
    {
        $this->table = $table;

        return $this;
    }

    public function status(): Collection
    {
        $this->ensureMigrationTableExists();

        $ran = $this->getRan();
        $files = $this->getMigrationFiles();

        return (new Collection($files))->map(function ($file) use ($ran) {
            $name = $this->getMigrationName($file);

            return [
                'name' => $name,
                'ran' => in_array($name, $ran, true),
            ];
        });
    }
}
