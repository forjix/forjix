<?php

declare(strict_types=1);

namespace Forjix\Console\Commands;

use Forjix\Console\Command;
use Forjix\Database\Connection;
use Forjix\Database\Migration\Migrator;

class MigrateRollback extends Command
{
    protected string $signature = 'migrate:rollback {--step=1} {--force}';
    protected string $description = 'Rollback the last database migration';

    public function handle(): int
    {
        if (!$this->confirmToProceed()) {
            return 0;
        }

        $migrator = $this->getMigrator();
        $steps = (int) $this->option('step') ?: 1;

        $this->info('Rolling back migrations...');

        $migrations = $migrator->rollback($steps);

        if (empty($migrations)) {
            $this->comment('Nothing to rollback.');
        } else {
            foreach ($migrations as $migration) {
                $this->info("Rolled back: {$migration}");
            }
        }

        return 0;
    }

    protected function getMigrator(): Migrator
    {
        $connection = $this->getConnection();
        $paths = [getcwd() . '/database/migrations'];

        return new Migrator($connection, $paths);
    }

    protected function getConnection(): Connection
    {
        $configPath = getcwd() . '/config/database.php';

        if (!file_exists($configPath)) {
            throw new \RuntimeException('Database configuration not found.');
        }

        $config = require $configPath;
        $default = $config['default'] ?? 'default';
        $connectionConfig = $config['connections'][$default] ?? [];

        return new Connection($connectionConfig);
    }

    protected function confirmToProceed(): bool
    {
        if ($this->option('force')) {
            return true;
        }

        $appConfigPath = getcwd() . '/config/app.php';
        if (file_exists($appConfigPath)) {
            $appConfig = require $appConfigPath;
            if (($appConfig['env'] ?? 'local') === 'production') {
                return $this->confirm('Are you sure you want to rollback migrations in production?');
            }
        }

        return true;
    }
}
