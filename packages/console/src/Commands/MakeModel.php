<?php

declare(strict_types=1);

namespace Forjix\Console\Commands;

use Forjix\Console\Command;

class MakeModel extends Command
{
    protected string $signature = 'make:model {name} {--m|migration} {--c|controller} {--r|resource}';
    protected string $description = 'Create a new model class';

    public function handle(): int
    {
        $name = $this->argument('name');
        $className = $this->getClassName($name);
        $namespace = $this->getNamespace($name);
        $path = $this->getPath($name);

        $content = $this->buildClass($className, $namespace);

        if (file_exists($path)) {
            $this->error("Model [{$name}] already exists!");
            return 1;
        }

        $this->ensureDirectory(dirname($path));
        file_put_contents($path, $content);

        $this->info("Model [{$name}] created successfully.");

        // Create migration if requested
        if ($this->option('migration') || $this->option('m')) {
            $this->call('make:migration', [
                'name' => 'create_' . $this->getTableName($className) . '_table',
            ]);
        }

        // Create controller if requested
        if ($this->option('controller') || $this->option('c')) {
            $args = ['name' => $className . 'Controller'];

            if ($this->option('resource') || $this->option('r')) {
                $args['--resource'] = true;
            }

            $this->call('make:controller', $args);
        }

        return 0;
    }

    protected function buildClass(string $className, string $namespace): string
    {
        $table = $this->getTableName($className);

        return <<<STUB
<?php

declare(strict_types=1);

namespace {$namespace};

use Forjix\Orm\Model;

class {$className} extends Model
{
    protected string \$table = '{$table}';

    protected array \$fillable = [
        //
    ];

    protected array \$casts = [
        //
    ];
}
STUB;
    }

    protected function getClassName(string $name): string
    {
        return basename(str_replace('\\', '/', $name));
    }

    protected function getNamespace(string $name): string
    {
        $parts = explode('/', str_replace('\\', '/', $name));
        array_pop($parts);

        if (empty($parts)) {
            return 'App\\Models';
        }

        return 'App\\Models\\' . implode('\\', $parts);
    }

    protected function getPath(string $name): string
    {
        $name = str_replace('\\', '/', $name);
        return getcwd() . '/app/Models/' . $name . '.php';
    }

    protected function getTableName(string $className): string
    {
        // Convert StudlyCase to snake_case and pluralize
        $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
        return $snake . 's';
    }

    protected function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}
