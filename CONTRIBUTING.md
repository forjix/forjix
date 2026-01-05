# Contributing to Forjix

Thank you for your interest in contributing to Forjix! This document provides guidelines and information for contributors.

## Code of Conduct

Be respectful and constructive. We welcome contributors of all experience levels. Critique code, not people.

## How to Contribute

### Reporting Bugs

Before submitting a bug report:

1. **Search existing issues** to avoid duplicates
2. **Use the latest version** to confirm the bug still exists
3. **Create a minimal reproduction** if possible

When submitting a bug report, include:

- Forjix version and PHP version
- Operating system
- Steps to reproduce
- Expected vs actual behavior
- Relevant code snippets or error messages

### Suggesting Features

Feature requests are welcome. Please:

1. **Check existing issues** for similar suggestions
2. **Explain the use case** - why is this feature needed?
3. **Consider scope** - does this belong in core or as a separate package?

### Submitting Pull Requests

1. **Fork the repository** and create a feature branch
2. **Follow code style** guidelines below
3. **Write tests** for new functionality
4. **Update documentation** if needed
5. **Submit PR** against the `main` branch

#### PR Checklist

- [ ] Code follows PSR-12 style
- [ ] All tests pass (`composer test`)
- [ ] Static analysis passes (`composer analyse`)
- [ ] New code has test coverage
- [ ] Documentation updated if needed
- [ ] Commit messages are clear and descriptive

## Development Setup

### Prerequisites

- PHP 8.2 or higher
- Composer
- Git

### Setting Up the Monorepo

```bash
# Clone the repository
git clone https://github.com/forjix/forjix.git
cd forjix

# Install dependencies
composer install

# Run tests for all packages
composer test
```

### Working with Individual Packages

Each package in `packages/` can be developed independently:

```bash
cd packages/orm
composer install
composer test
```

### Running Tests

```bash
# Run all tests
composer test

# Run tests for a specific package
cd packages/database
./vendor/bin/phpunit

# Run tests with coverage
./vendor/bin/phpunit --coverage-html coverage
```

### Static Analysis

```bash
# Run PHPStan
composer analyse

# Run for specific package
cd packages/core
./vendor/bin/phpstan analyse src
```

## Code Style

Forjix follows PSR-12 with additional conventions:

### General Rules

- Use `declare(strict_types=1)` in all PHP files
- Prefer early returns over nested conditionals
- Keep methods short and focused (under 20 lines ideally)
- Use meaningful variable and method names

### Type Declarations

Always use type declarations for parameters, return types, and properties:

```php
// Good
public function find(int $id): ?User
{
    return $this->query()->where('id', $id)->first();
}

// Bad
public function find($id)
{
    return $this->query()->where('id', $id)->first();
}
```

### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Classes | PascalCase | `QueryBuilder` |
| Methods | camelCase | `getConnection()` |
| Properties | camelCase | `$primaryKey` |
| Constants | UPPER_SNAKE | `CREATED_AT` |
| Variables | camelCase | `$userCount` |

### Documentation

Use PHPDoc blocks for:
- Public methods with non-obvious behavior
- Complex algorithms
- Classes explaining their purpose

```php
/**
 * Execute the query and return the first result or throw an exception.
 *
 * @throws ModelNotFoundException When no matching record is found
 */
public function firstOrFail(): static
{
    $result = $this->first();

    if ($result === null) {
        throw new ModelNotFoundException();
    }

    return $result;
}
```

Skip PHPDoc when types are self-documenting:

```php
// PHPDoc unnecessary - types tell the story
public function setTable(string $table): static
{
    $this->table = $table;
    return $this;
}
```

### Formatting

- 4 spaces for indentation (no tabs)
- Opening braces on same line for classes and methods
- One blank line between methods
- No trailing whitespace
- Files end with a single newline

```php
<?php

declare(strict_types=1);

namespace Forjix\Database;

class Connection
{
    protected \PDO $pdo;

    public function __construct(array $config)
    {
        $this->pdo = $this->createConnection($config);
    }

    public function query(string $sql, array $bindings = []): array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}
```

## Architecture Guidelines

### Package Independence

Packages should minimize dependencies on other packages:

```
forjix/support    - No dependencies (base utilities)
forjix/validation - Depends on support
forjix/database   - Depends on support
forjix/orm        - Depends on database, support
forjix/view       - Depends on support
forjix/http       - Depends on support
forjix/core       - Depends on support
forjix/console    - Depends on support
```

### SOLID Principles

**Single Responsibility**: Each class should have one reason to change.

```php
// Good - separate concerns
class Router { /* routing logic */ }
class RouteCompiler { /* route compilation */ }
class RouteCollection { /* route storage */ }

// Bad - multiple responsibilities
class Router { /* routing + compilation + storage */ }
```

**Open/Closed**: Extend behavior without modifying existing code.

```php
// Good - extensible via inheritance/composition
abstract class Command
{
    abstract public function handle(): int;
}

// Users extend, not modify
class MigrateCommand extends Command
{
    public function handle(): int { /* ... */ }
}
```

**Dependency Inversion**: Depend on abstractions.

```php
// Good - depends on interface
public function __construct(ConnectionInterface $connection)
{
    $this->connection = $connection;
}

// Bad - depends on concrete class
public function __construct(MySqlConnection $connection)
{
    $this->connection = $connection;
}
```

### Error Handling

- Throw specific exceptions, not generic `\Exception`
- Create custom exceptions for each package
- Include helpful error messages

```php
// Good
throw new ModelNotFoundException("User with ID {$id} not found");

// Bad
throw new \Exception("Not found");
```

### Immutability

Prefer immutable objects where practical, especially in the query builder:

```php
// Each method returns a new instance
public function where(string $column, mixed $value): static
{
    $clone = clone $this;
    $clone->wheres[] = [$column, '=', $value];
    return $clone;
}
```

## Testing Guidelines

### Test Structure

```
tests/
├── Unit/           # Isolated unit tests
│   ├── ModelTest.php
│   └── QueryBuilderTest.php
└── Feature/        # Integration tests
    └── DatabaseTest.php
```

### Writing Tests

- Test one behavior per test method
- Use descriptive test names
- Arrange-Act-Assert pattern

```php
public function testModelCanBeCreatedWithAttributes(): void
{
    // Arrange
    $attributes = ['name' => 'John', 'email' => 'john@example.com'];

    // Act
    $user = new User($attributes);

    // Assert
    $this->assertEquals('John', $user->name);
    $this->assertEquals('john@example.com', $user->email);
}
```

### Test Naming

Use descriptive names that explain the scenario:

```php
// Good
public function testFindReturnsNullWhenRecordDoesNotExist(): void
public function testValidationFailsWhenEmailIsMissing(): void

// Bad
public function testFind(): void
public function testValidation(): void
```

### Mocking

Use mocks sparingly. Prefer real implementations when practical:

```php
// Prefer in-memory SQLite for database tests
protected function setUp(): void
{
    $this->connection = new Connection([
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]);
}
```

## Commit Messages

Write clear, concise commit messages:

```
Short summary (50 chars or less)

More detailed explanation if needed. Wrap at 72 characters.
Explain what and why, not how (the code shows how).

- Bullet points are fine
- Use present tense ("Add feature" not "Added feature")
```

Examples:

```
Add whereIn method to query builder

Implement whereIn and whereNotIn methods for filtering
by multiple values. Uses prepared statements with
parameter binding for security.

Closes #42
```

```
Fix memory leak in view compiler cache

The compiler was holding references to compiled templates
indefinitely. Now uses weak references to allow garbage
collection.
```

## Release Process

Forjix uses semantic versioning (MAJOR.MINOR.PATCH):

- **PATCH**: Bug fixes, no API changes
- **MINOR**: New features, backward compatible
- **MAJOR**: Breaking changes

Releases are tagged in each package repository and the main monorepo.

## Getting Help

- **Questions**: Open a GitHub Discussion
- **Bugs**: Open a GitHub Issue
- **Security**: Email security concerns privately (do not open public issues)

## Recognition

Contributors are recognized in release notes. Significant contributors may be invited to join the core team.

---

Thank you for contributing to Forjix!
