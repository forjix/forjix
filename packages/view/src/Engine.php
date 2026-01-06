<?php

declare(strict_types=1);

namespace Forjix\View;

class Engine
{
    protected Compiler $compiler;
    protected array $paths = [];
    protected ?string $cachePath = null;
    protected array $sections = [];
    protected array $sectionStack = [];
    protected array $pushStack = [];
    protected array $pushes = [];
    protected ?string $parentView = null;
    protected array $shared = [];

    public function __construct(array $paths = [], ?string $cachePath = null)
    {
        $this->paths = $paths;
        $this->cachePath = $cachePath;
        $this->compiler = new Compiler();
    }

    public function make(string $view, array $data = []): View
    {
        return new View($this, $view, $data);
    }

    public function render(string $view, array $data = []): string
    {
        return $this->make($view, $data)->render();
    }

    public function renderContents(string $view, array $data = []): string
    {
        $path = $this->findView($view);

        if ($path === null) {
            throw new ViewNotFoundException("View [{$view}] not found.");
        }

        $compiled = $this->compile($path);
        $data = array_merge($this->shared, $data);

        return $this->evaluatePath($compiled, $data);
    }

    public function compile(string $path): string
    {
        if ($this->cachePath === null) {
            // No cache, compile on the fly
            return $this->compileString(file_get_contents($path));
        }

        $compiledPath = $this->getCompiledPath($path);

        if (!file_exists($compiledPath) || filemtime($path) > filemtime($compiledPath)) {
            $compiled = $this->compileString(file_get_contents($path));
            file_put_contents($compiledPath, $compiled);
        }

        return $compiledPath;
    }

    public function compileString(string $value): string
    {
        return $this->compiler->compile($value);
    }

    protected function evaluatePath(string $__path, array $__data): string
    {
        $obLevel = ob_get_level();

        ob_start();

        extract($__data);
        $__env = $this;

        try {
            if (str_starts_with($__path, '<?php')) {
                eval('?>' . $__path);
            } else {
                include $__path;
            }
        } catch (\Throwable $e) {
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }

            throw $e;
        }

        $content = ob_get_clean();

        // Handle template inheritance
        if ($this->parentView !== null) {
            $parent = $this->parentView;
            $this->parentView = null;
            return $this->renderContents($parent, $__data);
        }

        return $content;
    }

    protected function getCompiledPath(string $path): string
    {
        return $this->cachePath . '/' . sha1($path) . '.php';
    }

    public function findView(string $view): ?string
    {
        $view = str_replace('.', '/', $view);

        foreach ($this->paths as $path) {
            $fullPath = $path . '/' . $view . '.blade.php';
            if (file_exists($fullPath)) {
                return $fullPath;
            }

            $fullPath = $path . '/' . $view . '.php';
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return null;
    }

    public function exists(string $view): bool
    {
        return $this->findView($view) !== null;
    }

    public function addPath(string $path): void
    {
        $this->paths[] = $path;
    }

    public function prependPath(string $path): void
    {
        array_unshift($this->paths, $path);
    }

    public function getPaths(): array
    {
        return $this->paths;
    }

    public function setCachePath(?string $path): void
    {
        $this->cachePath = $path;
    }

    // Template Inheritance

    public function startExtends(string $view): void
    {
        $this->parentView = $view;
    }

    public function startSection(string $section, ?string $content = null): void
    {
        if ($content !== null) {
            $this->sections[$section] = $content;
            return;
        }

        $this->sectionStack[] = $section;
        ob_start();
    }

    public function endSection(): string
    {
        $last = array_pop($this->sectionStack);
        $this->sections[$last] = ob_get_clean();

        return $last;
    }

    public function yieldContent(string $section, string $default = ''): string
    {
        return $this->sections[$section] ?? $default;
    }

    public function yieldSection(): string
    {
        return $this->yieldContent($this->endSection());
    }

    public function yieldParent(): string
    {
        $section = end($this->sectionStack);

        return $this->sections[$section] ?? '';
    }

    public function hasSection(string $name): bool
    {
        return isset($this->sections[$name]);
    }

    public function sectionMissing(string $name): bool
    {
        return !$this->hasSection($name);
    }

    // Stacks

    public function startPush(string $section): void
    {
        $this->pushStack[] = $section;
        ob_start();
    }

    public function endPush(): void
    {
        $last = array_pop($this->pushStack);
        $content = ob_get_clean();

        if (!isset($this->pushes[$last])) {
            $this->pushes[$last] = [];
        }

        $this->pushes[$last][] = $content;
    }

    public function yieldPushContent(string $section, string $default = ''): string
    {
        if (!isset($this->pushes[$section])) {
            return $default;
        }

        return implode('', $this->pushes[$section]);
    }

    // Shared Data

    public function share(string|array $key, mixed $value = null): void
    {
        if (is_array($key)) {
            $this->shared = array_merge($this->shared, $key);
        } else {
            $this->shared[$key] = $value;
        }
    }

    public function getShared(): array
    {
        return $this->shared;
    }

    // Render helpers

    public function renderWhen(bool $condition, string $view, array $data = []): string
    {
        if (!$condition) {
            return '';
        }

        return $this->render($view, $data);
    }

    public function renderUnless(bool $condition, string $view, array $data = []): string
    {
        return $this->renderWhen(!$condition, $view, $data);
    }

    public function renderEach(string $view, array $data, string $iterator, string $empty = 'raw|'): string
    {
        $result = '';

        if (count($data) > 0) {
            foreach ($data as $key => $value) {
                $result .= $this->render($view, [
                    'key' => $key,
                    $iterator => $value,
                ]);
            }
        } elseif (str_starts_with($empty, 'raw|')) {
            $result = substr($empty, 4);
        } else {
            $result = $this->render($empty);
        }

        return $result;
    }

    // Custom Directives

    public function directive(string $name, callable $handler): void
    {
        $this->compiler->directive($name, $handler);
    }

    public function if(string $name, callable $callback): void
    {
        $this->compiler->if($name, $callback);
    }

    public function getCondition(string $name): ?callable
    {
        return $this->compiler->getCondition($name);
    }

    public function getCompiler(): Compiler
    {
        return $this->compiler;
    }
}
