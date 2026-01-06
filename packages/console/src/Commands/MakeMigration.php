<?php

declare(strict_types=1);

namespace Forjix\Console\Commands;

use Forjix\Console\Command;

class MakeMigration extends Command
{
    protected string $signature = 'make:migration {name} {--create=} {--table=}';
    protected string $description = 'Create a new migration file';

    public function handle(): int
    {
        $name = $this->argument('name');
        $create = $this->option('create');
        $table = $this->option('table');

        // Auto-detect table name from migration name
        if (!$create && !$table) {
            if (preg_match('/create_(\w+)_table/', $name, $matches)) {
                $create = $matches[1];
            } elseif (preg_match('/(?:add|remove|update)_\w+_(?:to|from|in)_(\w+)/', $name, $matches)) {
                $table = $matches[1];
            }
        }

        $className = $this->getClassName($name);
        $filename = $this->getFilename($name);
        $path = $this->getPath($filename);

        $content = $this->buildClass($className, $create, $table);

        $this->ensureDirectory(dirname($path));
        file_put_contents($path, $content);

        $this->info("Migration [{$filename}] created successfully.");
        return 0;
    }

    protected function buildClass(string $className, ?string $create, ?string $table): string
    {
        if ($create) {
            return $this->getCreateStub($className, $create);
        }

        if ($table) {
            return $this->getUpdateStub($className, $table);
        }

        return $this->getBlankStub($className);
    }

    protected function getCreateStub(string $className, string $table): string
    {
        return <<<STUB
<?php

declare(strict_types=1);

use Forjix\Database\Migration\Migration;
use Forjix\Database\Schema\Blueprint;

class {$className} extends Migration
{
    public function up(): void
    {
        \$this->schema->create('{$table}', function (Blueprint \$table) {
            \$table->id();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        \$this->schema->dropIfExists('{$table}');
    }
}
STUB;
    }

    protected function getUpdateStub(string $className, string $table): string
    {
        return <<<STUB
<?php

declare(strict_types=1);

use Forjix\Database\Migration\Migration;
use Forjix\Database\Schema\Blueprint;

class {$className} extends Migration
{
    public function up(): void
    {
        \$this->schema->table('{$table}', function (Blueprint \$table) {
            //
        });
    }

    public function down(): void
    {
        \$this->schema->table('{$table}', function (Blueprint \$table) {
            //
        });
    }
}
STUB;
    }

    protected function getBlankStub(string $className): string
    {
        return <<<STUB
<?php

declare(strict_types=1);

use Forjix\Database\Migration\Migration;

class {$className} extends Migration
{
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        //
    }
}
STUB;
    }

    protected function getClassName(string $name): string
    {
        // Convert snake_case to StudlyCase
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }

    protected function getFilename(string $name): string
    {
        $timestamp = date('Y_m_d_His');
        return $timestamp . '_' . $name;
    }

    protected function getPath(string $filename): string
    {
        return getcwd() . '/database/migrations/' . $filename . '.php';
    }

    protected function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}
