# Forjix

A lightweight, modular PHP framework for building modern web applications.

## Requirements

- PHP 8.2+
- Composer

## Installation

```bash
composer require forjix/forjix
```

Or create a new project using the skeleton:

```bash
composer create-project forjix/skeleton my-app
```

## Packages

Forjix is composed of the following modular packages:

| Package | Description |
|---------|-------------|
| [forjix/core](https://github.com/forjix/core) | Application container and service providers |
| [forjix/http](https://github.com/forjix/http) | HTTP routing, requests, and responses |
| [forjix/database](https://github.com/forjix/database) | Database connections, query builder, and migrations |
| [forjix/orm](https://github.com/forjix/orm) | Active Record ORM with relationships |
| [forjix/view](https://github.com/forjix/view) | Blade-like templating engine |
| [forjix/console](https://github.com/forjix/console) | CLI tools and commands |
| [forjix/validation](https://github.com/forjix/validation) | Data validation |
| [forjix/support](https://github.com/forjix/support) | Helper utilities and collections |

## Quick Start

```php
use Forjix\Core\Application;
use Forjix\Http\Router;

$app = new Application();
$router = $app->get(Router::class);

$router->get('/', function () {
    return 'Hello, Forjix!';
});

$app->run();
```

## License

MIT
