# Forjix Design Philosophy

This document outlines the core principles and design decisions that guide the development of the Forjix PHP framework.

## Core Principles

### 1. Simplicity Over Complexity

Forjix prioritizes clarity and simplicity. Every component should be easy to understand, use, and extend. We reject unnecessary abstraction layers that obscure what the code actually does.

```php
// Simple, clear routing
$router->get('/users/{id}', [UserController::class, 'show']);

// Simple model definition
class User extends Model
{
    protected array $fillable = ['name', 'email'];
}
```

The framework should feel intuitive. If a developer needs to constantly reference documentation for basic operations, we've failed.

### 2. Modularity and Independence

Forjix is built as a collection of independent packages that work together seamlessly but can also be used standalone:

| Package | Purpose | Standalone Use |
|---------|---------|----------------|
| `forjix/core` | Container and service providers | Yes |
| `forjix/http` | Request/response handling | Yes |
| `forjix/database` | Database abstraction | Yes |
| `forjix/orm` | Active Record ORM | Requires database |
| `forjix/view` | Template engine | Yes |
| `forjix/validation` | Data validation | Yes |
| `forjix/support` | Utilities and helpers | Yes |
| `forjix/console` | CLI tools | Yes |

This modular architecture provides several benefits:

- **Reduced overhead**: Include only what you need
- **Easier testing**: Test packages in isolation
- **Flexibility**: Replace or extend individual components
- **Maintainability**: Focused, single-purpose codebases

### 3. Modern PHP First

Forjix embraces modern PHP (8.2+) without backward compatibility concerns. We use:

- **Strict types**: All code uses `declare(strict_types=1)`
- **Union types and nullable types**: For precise type definitions
- **Named arguments**: For clearer function calls
- **Match expressions**: Instead of verbose switch statements
- **Constructor property promotion**: For cleaner class definitions
- **Attributes**: For metadata where appropriate
- **Enums**: For fixed sets of values

```php
// Modern PHP features throughout
public function castAttribute(string $key, mixed $value): mixed
{
    return match ($this->casts[$key]) {
        'int', 'integer' => (int) $value,
        'bool', 'boolean' => (bool) $value,
        'array', 'json' => json_decode($value, true),
        'datetime' => new \DateTime($value),
        default => $value,
    };
}
```

### 4. Convention Over Configuration

Sensible defaults eliminate boilerplate. The framework assumes conventions that can be overridden when needed:

```php
class Task extends Model
{
    // Table name: 'tasks' (pluralized snake_case)
    // Primary key: 'id'
    // Timestamps: created_at, updated_at
    // Foreign key for relations: 'task_id'
}

// Override only when conventions don't fit
class Person extends Model
{
    protected string $table = 'people';
    protected string $primaryKey = 'person_id';
}
```

This approach reduces configuration files and keeps application code focused on business logic.

### 5. Explicit Over Implicit

While we embrace conventions, we avoid "magic" that obscures behavior. The framework should be predictable:

```php
// Explicit relationship definitions
public function tasks(): HasMany
{
    return $this->hasMany(Task::class);
}

// Explicit middleware assignment
$router->group(['middleware' => [AuthMiddleware::class]], function ($router) {
    $router->get('/dashboard', [DashboardController::class, 'index']);
});
```

When a developer reads Forjix application code, they should understand what happens without deep framework knowledge.

## Architectural Decisions

### Service Container

The application container is the foundation of Forjix. It manages object instantiation and dependency injection:

```php
// Binding
$app->singleton(DatabaseManager::class, fn($app) => new DatabaseManager($app->config()));

// Resolution with automatic dependency injection
$controller = $app->make(TaskController::class);
```

The container:
- Supports singletons and transient bindings
- Resolves dependencies automatically via reflection
- Allows interface-to-implementation binding
- Implements PSR-11 ContainerInterface

### Service Providers

Service providers organize bootstrapping logic into discrete, focused units:

```php
class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DatabaseManager::class, function ($app) {
            return new DatabaseManager($app->config()->get('database'));
        });
    }

    public function boot(): void
    {
        Model::setConnection($this->app->make(DatabaseManager::class)->connection());
    }
}
```

This separation ensures:
- Clear initialization order (register, then boot)
- Lazy loading when appropriate
- Easy testing and replacement

### Active Record ORM

We chose Active Record over Data Mapper for its simplicity and developer ergonomics:

```php
// Intuitive CRUD operations
$task = Task::create(['title' => 'New task']);
$task->status = 'completed';
$task->save();
$task->delete();

// Expressive queries
$tasks = Task::where('status', 'pending')
    ->where('priority', 'high')
    ->orderBy('due_date')
    ->get();
```

Active Record provides:
- Objects that know how to persist themselves
- Cleaner syntax for common operations
- Relationship traversal via method calls
- Dirty tracking for efficient updates

The tradeoff is tighter coupling between domain objects and persistence, which we accept for typical web application use cases.

### Query Builder

The query builder provides a fluent, secure interface to SQL:

```php
$users = $db->table('users')
    ->select('name', 'email')
    ->where('active', true)
    ->whereIn('role', ['admin', 'moderator'])
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();
```

Key design decisions:
- **Immutable query objects**: Each method returns a new instance
- **Prepared statements**: All user input is parameterized
- **Database agnostic**: Same API for SQLite, MySQL, PostgreSQL
- **No string concatenation**: SQL injection is impossible through the builder

### Blade-Inspired Templates

The view engine uses familiar Blade syntax for templates:

```php
@extends('layouts.app')

@section('content')
    @foreach($tasks as $task)
        <div class="task {{ $task->isOverdue() ? 'overdue' : '' }}">
            {{ $task->title }}
        </div>
    @endforeach
@endsection
```

Design decisions:
- **Compilation to PHP**: Templates compile to cached PHP for performance
- **Automatic escaping**: `{{ }}` escapes output; `{!! !!}` for raw
- **PHP fallback**: Plain PHP works alongside directives
- **Minimal directives**: Only essential control structures

### Middleware Pipeline

HTTP middleware uses a simple pipeline pattern:

```php
class AuthMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Session::has('user_id')) {
            return new RedirectResponse('/login');
        }

        return $next($request);
    }
}
```

The pipeline is:
- **Linear and predictable**: Request flows through middleware in order
- **Symmetric**: Response flows back through the same middleware
- **Composable**: Group middleware for routes

### Validation

The validator provides declarative, chainable rules:

```php
$validator = new Validator($data, [
    'email' => 'required|email|unique:users',
    'password' => 'required|min:8|confirmed',
    'age' => 'nullable|integer|between:18,120',
]);

if ($validator->fails()) {
    return $validator->errors();
}
```

Design principles:
- **Declarative rules**: String syntax for common cases
- **Extensible**: Custom rules via closures or classes
- **Informative errors**: Human-readable messages
- **Fail-fast option**: Stop on first error when appropriate

## What Forjix Is Not

Understanding what we choose not to do is equally important:

### Not a Kitchen Sink

Forjix includes only essential components. We don't bundle:
- Authentication systems (provide building blocks instead)
- Admin panels or scaffolding
- Asset compilation
- Queue workers
- Caching drivers

These are application concerns. The framework provides foundations; applications build features.

### Not Backward Compatible at All Costs

We target PHP 8.2+ and will adopt new language features as they become available. This keeps the codebase clean and leverages modern capabilities.

### Not Configuration-Heavy

Unlike frameworks with dozens of configuration files, Forjix applications typically need:
- `app.php` - Application settings
- `database.php` - Database connections
- `view.php` - Template paths

Most configuration uses sensible defaults.

### Not Enterprise Framework

Forjix targets small to medium applications where developer productivity matters more than enterprise patterns like CQRS, event sourcing, or domain-driven design. These patterns are possible but not forced.

## Design Tradeoffs

Every framework makes tradeoffs. Here are ours:

| We Chose | Over | Because |
|----------|------|---------|
| Active Record | Data Mapper | Simpler API for typical CRUD applications |
| String-based validation rules | Builder pattern | More concise, familiar syntax |
| Compiled templates | Runtime parsing | Better performance in production |
| PSR-4 autoloading | Custom loaders | Standard, predictable behavior |
| Method chaining | Configuration objects | Fluent, readable code |
| Convention-based routing | Annotation-based | Explicit route files, easier debugging |

## Contributing to Forjix

When contributing, keep these principles in mind:

1. **Would a newcomer understand this?** Optimize for readability.
2. **Is this the simplest solution?** Complexity should be justified.
3. **Does this belong in the framework?** Core features only.
4. **Is this testable?** All code should be unit testable.
5. **Does this follow existing patterns?** Consistency matters.

## Influences and Acknowledgments

Forjix draws inspiration from:

- **Laravel**: Service container, Eloquent patterns, Blade syntax
- **Symfony**: HTTP foundation concepts, component architecture
- **Rails**: Convention over configuration, Active Record
- **Express.js**: Middleware pipeline simplicity

We stand on the shoulders of giants and aim to distill their best ideas into a focused, modern PHP framework.

---

*The goal of Forjix is to be a framework you can understand completely. Every line of code should be clear in purpose. When you build with Forjix, you're not fighting the framework—you're collaborating with it.*
