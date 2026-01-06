<?php

declare(strict_types=1);

namespace Forjix\Console\Commands;

use Forjix\Console\Command;

class RoutesList extends Command
{
    protected string $signature = 'routes:list {--method=} {--name=} {--path=}';
    protected string $description = 'List all registered routes';

    public function handle(): int
    {
        $this->info('Application Routes');
        $this->newLine();

        $routes = $this->getRoutes();

        if (empty($routes)) {
            $this->comment('No routes registered.');
            return 0;
        }

        // Filter routes
        $methodFilter = $this->option('method');
        $nameFilter = $this->option('name');
        $pathFilter = $this->option('path');

        $filteredRoutes = array_filter($routes, function ($route) use ($methodFilter, $nameFilter, $pathFilter) {
            if ($methodFilter && !in_array(strtoupper($methodFilter), $route['methods'])) {
                return false;
            }
            if ($nameFilter && !str_contains($route['name'] ?? '', $nameFilter)) {
                return false;
            }
            if ($pathFilter && !str_contains($route['uri'], $pathFilter)) {
                return false;
            }
            return true;
        });

        if (empty($filteredRoutes)) {
            $this->comment('No routes match the given criteria.');
            return 0;
        }

        $this->table(
            ['Method', 'URI', 'Name', 'Action', 'Middleware'],
            array_map(fn($route) => [
                implode('|', $route['methods']),
                $route['uri'],
                $route['name'] ?? '',
                $route['action'],
                implode(', ', $route['middleware']),
            ], $filteredRoutes)
        );

        return 0;
    }

    protected function getRoutes(): array
    {
        // This would normally get routes from the router
        // For now, we'll return an example structure
        $routesFile = getcwd() . '/routes/web.php';

        if (!file_exists($routesFile)) {
            return [];
        }

        // In a real implementation, this would use the router
        return [];
    }
}
