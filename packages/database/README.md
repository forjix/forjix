# Forjix Database

Database abstraction layer for the Forjix framework with query builder, schema builder, and migrations.

## Installation

```bash
composer require forjix/database
```

## Requirements

- PHP 8.2+
- PDO extension

## Configuration

```php
use Forjix\Database\DatabaseManager;

$manager = new DatabaseManager([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'myapp',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
]);
```

## Query Builder

```php
// Select
$users = $db->table('users')
    ->where('active', true)
    ->orderBy('name')
    ->get();

// Insert
$db->table('users')->insert([
    'name' => 'John',
    'email' => 'john@example.com',
]);

// Update
$db->table('users')
    ->where('id', 1)
    ->update(['name' => 'Jane']);

// Delete
$db->table('users')
    ->where('id', 1)
    ->delete();
```

## Schema Builder

```php
use Forjix\Database\Schema\Schema;

Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->timestamps();
});
```

## Migrations

```php
use Forjix\Database\Migration\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
}
```

## License

GPL-3.0
