<?php

declare(strict_types=1);

namespace Forjix\Core;

use Forjix\Core\Config\Config;
use Forjix\Core\Container\Container;

class Application extends Container
{
    public const VERSION = '1.0.0';

    protected string $basePath;

    protected bool $hasBeenBootstrapped = false;

    protected bool $booted = false;

    protected array $serviceProviders = [];

    protected array $loadedProviders = [];

    protected array $bootingCallbacks = [];

    protected array $bootedCallbacks = [];

    protected array $terminatingCallbacks = [];

    public function __construct(?string $basePath = null)
    {
        if ($basePath !== null) {
            $this->setBasePath($basePath);
        }

        $this->registerBaseBindings();
        $this->registerCoreContainerAliases();
    }

    protected function registerBaseBindings(): void
    {
        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance(Container::class, $this);
        $this->instance(Application::class, $this);

        $this->singleton(Config::class, fn() => new Config());
        $this->alias(Config::class, 'config');
    }

    protected function registerCoreContainerAliases(): void
    {
        $aliases = [
            'app' => [self::class, Container::class, \Psr\Container\ContainerInterface::class],
        ];

        foreach ($aliases as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($key, $alias);
            }
        }
    }

    public function setBasePath(string $basePath): static
    {
        $this->basePath = rtrim($basePath, '\/');

        $this->bindPathsInContainer();

        return $this;
    }

    protected function bindPathsInContainer(): void
    {
        $this->instance('path.base', $this->basePath());
        $this->instance('path.app', $this->appPath());
        $this->instance('path.config', $this->configPath());
        $this->instance('path.public', $this->publicPath());
        $this->instance('path.storage', $this->storagePath());
        $this->instance('path.database', $this->databasePath());
        $this->instance('path.resources', $this->resourcePath());
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path !== '' ? DIRECTORY_SEPARATOR . $path : '');
    }

    public function appPath(string $path = ''): string
    {
        return $this->basePath('app') . ($path !== '' ? DIRECTORY_SEPARATOR . $path : '');
    }

    public function configPath(string $path = ''): string
    {
        return $this->basePath('config') . ($path !== '' ? DIRECTORY_SEPARATOR . $path : '');
    }

    public function publicPath(string $path = ''): string
    {
        return $this->basePath('public') . ($path !== '' ? DIRECTORY_SEPARATOR . $path : '');
    }

    public function storagePath(string $path = ''): string
    {
        return $this->basePath('storage') . ($path !== '' ? DIRECTORY_SEPARATOR . $path : '');
    }

    public function databasePath(string $path = ''): string
    {
        return $this->basePath('database') . ($path !== '' ? DIRECTORY_SEPARATOR . $path : '');
    }

    public function resourcePath(string $path = ''): string
    {
        return $this->basePath('resources') . ($path !== '' ? DIRECTORY_SEPARATOR . $path : '');
    }

    public function config(): Config
    {
        return $this->make(Config::class);
    }

    public function version(): string
    {
        return static::VERSION;
    }

    public function register(ServiceProvider|string $provider, bool $force = false): ServiceProvider
    {
        if (($registered = $this->getProvider($provider)) && !$force) {
            return $registered;
        }

        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        $provider->register();

        $this->markAsRegistered($provider);

        if ($this->isBooted()) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    public function getProvider(ServiceProvider|string $provider): ?ServiceProvider
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return $this->serviceProviders[$name] ?? null;
    }

    public function getProviders(): array
    {
        return $this->serviceProviders;
    }

    protected function markAsRegistered(ServiceProvider $provider): void
    {
        $this->serviceProviders[get_class($provider)] = $provider;
        $this->loadedProviders[get_class($provider)] = true;
    }

    public function loadConfigurationFiles(): void
    {
        $this->config()->load($this->configPath());
    }

    public function registerConfiguredProviders(): void
    {
        $providers = $this->config()->get('app.providers', []);

        foreach ($providers as $provider) {
            $this->register($provider);
        }
    }

    public function boot(): void
    {
        if ($this->isBooted()) {
            return;
        }

        $this->fireAppCallbacks($this->bootingCallbacks);

        foreach ($this->serviceProviders as $provider) {
            $this->bootProvider($provider);
        }

        $this->booted = true;

        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    protected function bootProvider(ServiceProvider $provider): void
    {
        $provider->callBootingCallbacks();

        $provider->boot();

        $provider->callBootedCallbacks();
    }

    public function booting(callable $callback): void
    {
        $this->bootingCallbacks[] = $callback;
    }

    public function booted(callable $callback): void
    {
        $this->bootedCallbacks[] = $callback;

        if ($this->isBooted()) {
            $callback($this);
        }
    }

    protected function fireAppCallbacks(array &$callbacks): void
    {
        $index = 0;

        while ($index < count($callbacks)) {
            $callbacks[$index]($this);
            $index++;
        }
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }

    public function bootstrapWith(array $bootstrappers): void
    {
        $this->hasBeenBootstrapped = true;

        foreach ($bootstrappers as $bootstrapper) {
            $this->make($bootstrapper)->bootstrap($this);
        }
    }

    public function hasBeenBootstrapped(): bool
    {
        return $this->hasBeenBootstrapped;
    }

    public function terminating(callable $callback): static
    {
        $this->terminatingCallbacks[] = $callback;

        return $this;
    }

    public function terminate(): void
    {
        foreach ($this->terminatingCallbacks as $callback) {
            $this->call($callback);
        }
    }

    public function environment(string ...$environments): string|bool
    {
        if (count($environments) > 0) {
            $env = $this->config()->get('app.env', 'production');

            return in_array($env, $environments, true);
        }

        return $this->config()->get('app.env', 'production');
    }

    public function isLocal(): bool
    {
        return $this->environment('local');
    }

    public function isProduction(): bool
    {
        return $this->environment('production');
    }

    public function runningInConsole(): bool
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }

    public function runningUnitTests(): bool
    {
        return $this->environment('testing');
    }

    public function isDownForMaintenance(): bool
    {
        return file_exists($this->storagePath('framework/down'));
    }

    public function abort(int $code, string $message = '', array $headers = []): never
    {
        throw new \Forjix\Http\Exceptions\HttpException($code, $message, null, $headers);
    }
}
