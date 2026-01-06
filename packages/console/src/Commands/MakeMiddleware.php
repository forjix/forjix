<?php

declare(strict_types=1);

namespace Forjix\Console\Commands;

use Forjix\Console\Command;

class MakeMiddleware extends Command
{
    protected string $signature = 'make:middleware {name}';
    protected string $description = 'Create a new middleware class';

    public function handle(): int
    {
        $name = $this->argument('name');
        $className = $this->getClassName($name);
        $namespace = $this->getNamespace($name);
        $path = $this->getPath($name);

        $content = $this->buildClass($className, $namespace);

        if (file_exists($path)) {
            $this->error("Middleware [{$name}] already exists!");
            return 1;
        }

        $this->ensureDirectory(dirname($path));
        file_put_contents($path, $content);

        $this->info("Middleware [{$name}] created successfully.");
        return 0;
    }

    protected function buildClass(string $className, string $namespace): string
    {
        return <<<STUB
<?php

declare(strict_types=1);

namespace {$namespace};

use Closure;
use Forjix\Http\Middleware\MiddlewareInterface;
use Forjix\Http\Request;
use Forjix\Http\Response;

class {$className} implements MiddlewareInterface
{
    public function handle(Request \$request, Closure \$next): Response
    {
        return \$next(\$request);
    }
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
            return 'App\\Middleware';
        }

        return 'App\\Middleware\\' . implode('\\', $parts);
    }

    protected function getPath(string $name): string
    {
        $name = str_replace('\\', '/', $name);
        return getcwd() . '/app/Middleware/' . $name . '.php';
    }

    protected function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}
