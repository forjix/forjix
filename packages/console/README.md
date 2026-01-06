# Forjix Console

CLI tools and command framework for the Forjix framework.

## Installation

```bash
composer require forjix/console
```

## Built-in Commands

- `serve` - Start the PHP development server
- `make:controller` - Generate a new controller class
- `make:model` - Generate a new model class
- `make:migration` - Generate a new database migration
- `make:middleware` - Generate a new middleware class
- `migrate` - Run pending database migrations
- `migrate:rollback` - Rollback the last migration batch
- `routes:list` - Display all registered routes
- `cache:clear` - Clear the application cache

## Creating Custom Commands

```php
use Forjix\Console\Command;
use Forjix\Console\Input;
use Forjix\Console\Output;

class GreetCommand extends Command
{
    protected string $name = 'greet';
    protected string $description = 'Greet a user';

    public function handle(Input $input, Output $output): int
    {
        $name = $input->argument('name', 'World');
        $output->line("Hello, {$name}!");

        return 0;
    }
}
```

## Usage

```php
use Forjix\Console\Application;

$app = new Application('MyApp', '1.0.0');
$app->add(new GreetCommand());
$app->run();
```

## License

GPL-3.0
