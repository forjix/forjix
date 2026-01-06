<?php

declare(strict_types=1);

namespace Forjix\Console\Commands;

use Forjix\Console\Command;

class MakeController extends Command
{
    protected string $signature = 'make:controller {name} {--resource} {--api}';
    protected string $description = 'Create a new controller class';

    public function handle(): int
    {
        $name = $this->argument('name');
        $resource = $this->option('resource');
        $api = $this->option('api');

        $className = $this->getClassName($name);
        $namespace = $this->getNamespace($name);
        $path = $this->getPath($name);

        $content = $this->buildClass($className, $namespace, $resource, $api);

        if (file_exists($path)) {
            $this->error("Controller [{$name}] already exists!");
            return 1;
        }

        $this->ensureDirectory(dirname($path));
        file_put_contents($path, $content);

        $this->info("Controller [{$name}] created successfully.");
        return 0;
    }

    protected function buildClass(string $className, string $namespace, bool $resource, bool $api): string
    {
        $stub = $resource ? $this->getResourceStub($api) : $this->getBasicStub();

        return str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $className],
            $stub
        );
    }

    protected function getBasicStub(): string
    {
        return <<<'STUB'
<?php

declare(strict_types=1);

namespace {{ namespace }};

use Forjix\Http\Attributes\Controller;
use Forjix\Http\Attributes\Get;
use Forjix\Http\Request;
use Forjix\Http\Response;

#[Controller]
class {{ class }}
{
    #[Get]
    public function index(Request $request): Response
    {
        //
    }
}
STUB;
    }

    protected function getResourceStub(bool $api): string
    {
        if ($api) {
            return <<<'STUB'
<?php

declare(strict_types=1);

namespace {{ namespace }};

use Forjix\Http\Attributes\Controller;
use Forjix\Http\Attributes\Delete;
use Forjix\Http\Attributes\Get;
use Forjix\Http\Attributes\Post;
use Forjix\Http\Attributes\Put;
use Forjix\Http\JsonResponse;
use Forjix\Http\Request;

#[Controller]
class {{ class }}
{
    #[Get]
    public function index(): JsonResponse
    {
        //
    }

    #[Post]
    public function store(Request $request): JsonResponse
    {
        //
    }

    #[Get('/{id}')]
    public function show(int $id): JsonResponse
    {
        //
    }

    #[Put('/{id}')]
    public function update(Request $request, int $id): JsonResponse
    {
        //
    }

    #[Delete('/{id}')]
    public function destroy(int $id): JsonResponse
    {
        //
    }
}
STUB;
        }

        return <<<'STUB'
<?php

declare(strict_types=1);

namespace {{ namespace }};

use Forjix\Http\Attributes\Controller;
use Forjix\Http\Attributes\Delete;
use Forjix\Http\Attributes\Get;
use Forjix\Http\Attributes\Post;
use Forjix\Http\Attributes\Put;
use Forjix\Http\Request;
use Forjix\Http\Response;

#[Controller]
class {{ class }}
{
    #[Get]
    public function index(): Response
    {
        //
    }

    #[Get('/create')]
    public function create(): Response
    {
        //
    }

    #[Post]
    public function store(Request $request): Response
    {
        //
    }

    #[Get('/{id}')]
    public function show(int $id): Response
    {
        //
    }

    #[Get('/{id}/edit')]
    public function edit(int $id): Response
    {
        //
    }

    #[Put('/{id}')]
    public function update(Request $request, int $id): Response
    {
        //
    }

    #[Delete('/{id}')]
    public function destroy(int $id): Response
    {
        //
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
            return 'App\\Controllers';
        }

        return 'App\\Controllers\\' . implode('\\', $parts);
    }

    protected function getPath(string $name): string
    {
        $name = str_replace('\\', '/', $name);
        return getcwd() . '/app/Controllers/' . $name . '.php';
    }

    protected function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}
