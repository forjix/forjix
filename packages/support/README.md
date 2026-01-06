# Forjix Support

Utility classes and helper functions for the Forjix framework.

## Installation

```bash
composer require forjix/support
```

## Collections

Fluent wrapper for working with arrays of data.

```php
use Forjix\Support\Collection;

$collection = collect([1, 2, 3, 4, 5]);

$filtered = $collection
    ->filter(fn($n) => $n > 2)
    ->map(fn($n) => $n * 2)
    ->values();
// [6, 8, 10]

// Available methods
$collection->first();
$collection->last();
$collection->count();
$collection->isEmpty();
$collection->pluck('name');
$collection->groupBy('category');
$collection->sortBy('name');
$collection->unique();
$collection->flatten();
```

## Array Helpers

```php
use Forjix\Support\Arr;

Arr::get($array, 'user.name', 'default');
Arr::set($array, 'user.name', 'John');
Arr::has($array, 'user.email');
Arr::only($array, ['name', 'email']);
Arr::except($array, ['password']);
Arr::flatten($array);
Arr::dot($array);
```

## String Helpers

```php
use Forjix\Support\Str;

Str::camel('hello_world');      // helloWorld
Str::snake('helloWorld');       // hello_world
Str::kebab('helloWorld');       // hello-world
Str::studly('hello_world');     // HelloWorld
Str::slug('Hello World');       // hello-world
Str::contains('Hello', 'ell'); // true
Str::startsWith('Hello', 'He'); // true
Str::endsWith('Hello', 'lo');   // true
Str::random(16);                // random string
```

## Pipeline

Chain operations on a value.

```php
use Forjix\Support\Pipeline;

$result = (new Pipeline())
    ->send($request)
    ->through([
        AuthMiddleware::class,
        LoggingMiddleware::class,
    ])
    ->then(fn($request) => $handler->handle($request));
```

## Fluent

Fluent interface for building configuration objects.

```php
use Forjix\Support\Fluent;

$config = new Fluent(['name' => 'App']);
$config->version('1.0');
echo $config->name; // App
```

## License

GPL-3.0
