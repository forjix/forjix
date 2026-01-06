<?php

declare(strict_types=1);

namespace Forjix\Console\Commands;

use Forjix\Console\Command;
use Forjix\Database\Connection;
use Forjix\Database\Migration\Migrator;

class Migrate extends Command
{
    protected string $signature = 'migrate {--seed} {--force}';
    protected string $description = 'Run the database migrations';

    public function handle(): int
    {
        if (!$this->confirmToProceed()) {
            return 0;
        }

        $migrator = $this->getMigrator();

        $this->info('Running migrations...');

        $migrations = $migrator->run();

        if (empty($migrations)) {
            $this->comment('Nothing to migrate.');
        } else {
            foreach ($migrations as $migration) {
                $name = basename($migration, '.php');
                $this->info("Migrated: {$name}");
            }
        }

        if ($this->option('seed')) {
            $this->call('db:seed');
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

        // Check if in production
        $appConfigPath = getcwd() . '/config/app.php';
        if (file_exists($appConfigPath)) {
            $appConfig = require $appConfigPath;
            if (($appConfig['env'] ?? 'local') === 'production') {
                return $this->confirm('Are you sure you want to run migrations in production?');
            }
        }

        return true;
    }
}
