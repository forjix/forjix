<?php

declare(strict_types=1);

namespace Forjix\Console\Commands;

use Forjix\Console\Command;

class CacheClear extends Command
{
    protected string $signature = 'cache:clear';
    protected string $description = 'Flush the application cache';

    public function handle(): int
    {
        $this->clearViewCache();
        $this->clearConfigCache();
        $this->clearRouteCache();

        $this->info('Application cache cleared successfully.');
        return 0;
    }

    protected function clearViewCache(): void
    {
        $viewCachePath = getcwd() . '/storage/views';

        if (is_dir($viewCachePath)) {
            $files = glob($viewCachePath . '/*.php');
            foreach ($files as $file) {
                unlink($file);
            }
            $this->comment('View cache cleared.');
        }
    }

    protected function clearConfigCache(): void
    {
        $configCachePath = getcwd() . '/storage/cache/config.php';

        if (file_exists($configCachePath)) {
            unlink($configCachePath);
            $this->comment('Config cache cleared.');
        }
    }

    protected function clearRouteCache(): void
    {
        $routeCachePath = getcwd() . '/storage/cache/routes.php';

        if (file_exists($routeCachePath)) {
            unlink($routeCachePath);
            $this->comment('Route cache cleared.');
        }
    }
}
