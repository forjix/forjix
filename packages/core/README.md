# Forjix Core

Core components for the Forjix framework including the application container and service providers.

## Installation

```bash
composer require forjix/core
```

## Components

### Application Container

A PSR-11 compatible dependency injection container with auto-wiring support.

```php
use Forjix\Core\Application;

$app = new Application();

// Bind a service
$app->bind(LoggerInterface::class, FileLogger::class);

// Bind a singleton
$app->singleton(Database::class, function ($app) {
    return new Database($app->get(Config::class));
});

// Resolve a service
$logger = $app->get(LoggerInterface::class);
```

### Service Providers

Register services and bootstrap application components.

```php
use Forjix\Core\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Database::class, function ($app) {
            return new Database($app->get(Config::class));
        });
    }

    public function boot(): void
    {
        // Called after all providers are registered
    }
}
```

### Configuration

```php
use Forjix\Core\Config\Config;

$config = new Config('/path/to/config');

$debug = $config->get('app.debug', false);
$config->set('app.timezone', 'UTC');
```

## License

GPL-3.0
