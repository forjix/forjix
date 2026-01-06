<?php

declare(strict_types=1);

namespace Forjix\Core;

abstract class ServiceProvider
{
    protected Application $app;

    protected array $singletons = [];

    protected bool $booted = false;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }

    public function callBootingCallbacks(): void
    {
        //
    }

    public function callBootedCallbacks(): void
    {
        //
    }

    protected function loadConfig(string $path, string $key): void
    {
        $this->app->config()->set($key, require $path);
    }

    protected function loadRoutes(string $path): void
    {
        require $path;
    }

    protected function loadViews(string $path, string $namespace): void
    {
        $this->app->config()->push('view.paths', [
            'namespace' => $namespace,
            'path' => $path,
        ]);
    }

    protected function loadMigrations(string $path): void
    {
        $this->app->config()->push('database.migrations', $path);
    }

    protected function publishes(array $paths, string $group = 'default'): void
    {
        $this->app->config()->push("publishing.{$group}", $paths);
    }

    public function provides(): array
    {
        return [];
    }

    public function when(): array
    {
        return [];
    }

    public function isDeferred(): bool
    {
        return false;
    }
}
