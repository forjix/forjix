<?php

declare(strict_types=1);

namespace Forjix\View;

class Compiler
{
    protected array $compilers = [
        'Comments',
        'Extensions',
        'Statements',
        'Echos',
    ];

    protected array $rawBlocks = [];
    protected array $customDirectives = [];
    protected array $conditions = [];
    protected string $contentTags = ['{{', '}}'];
    protected string $escapedTags = ['{{{', '}}}'];
    protected string $rawTags = ['{!!', '!!}'];

    public function compile(string $value): string
    {
        $this->rawBlocks = [];

        $value = $this->storeRawBlocks($value);

        foreach ($this->compilers as $type) {
            $value = $this->{"compile{$type}"}($value);
        }

        $value = $this->restoreRawBlocks($value);

        return $value;
    }

    protected function storeRawBlocks(string $value): string
    {
        // Store @verbatim blocks
        $value = preg_replace_callback('/@verbatim(.*?)@endverbatim/s', function ($matches) {
            $this->rawBlocks[] = $matches[1];
            return '@__raw_block_' . (count($this->rawBlocks) - 1) . '__@';
        }, $value);

        // Store @php blocks
        $value = preg_replace_callback('/@php(.*?)@endphp/s', function ($matches) {
            $this->rawBlocks[] = "<?php{$matches[1]}?>";
            return '@__raw_block_' . (count($this->rawBlocks) - 1) . '__@';
        }, $value);

        return $value;
    }

    protected function restoreRawBlocks(string $value): string
    {
        return preg_replace_callback('/@__raw_block_(\d+)__@/', function ($matches) {
            return $this->rawBlocks[$matches[1]];
        }, $value);
    }

    protected function compileComments(string $value): string
    {
        return preg_replace('/\{\{--(.*?)--\}\}/s', '', $value);
    }

    protected function compileExtensions(string $value): string
    {
        foreach ($this->customDirectives as $name => $handler) {
            $value = preg_replace_callback(
                "/@{$name}(?:\s*\((.*?)\))?/s",
                fn($matches) => call_user_func($handler, $matches[1] ?? ''),
                $value
            );
        }

        return $value;
    }

    protected function compileStatements(string $value): string
    {
        return preg_replace_callback(
            '/\B@(@?\w+(?:::\w+)?)([ \t]*)(\((.*?)\))?/s',
            fn($match) => $this->compileStatement($match),
            $value
        );
    }

    protected function compileStatement(array $match): string
    {
        $directive = $match[1];

        // Handle escaped directives
        if (str_starts_with($directive, '@')) {
            return substr($match[0], 1);
        }

        $expression = $match[4] ?? '';

        return match ($directive) {
            'if' => $this->compileIf($expression),
            'elseif' => $this->compileElseIf($expression),
            'else' => $this->compileElse(),
            'endif' => $this->compileEndIf(),
            'unless' => $this->compileUnless($expression),
            'endunless' => $this->compileEndUnless(),
            'isset' => $this->compileIsset($expression),
            'endisset' => $this->compileEndIsset(),
            'empty' => $this->compileEmpty($expression),
            'endempty' => $this->compileEndEmpty(),
            'for' => $this->compileFor($expression),
            'endfor' => $this->compileEndFor(),
            'foreach' => $this->compileForeach($expression),
            'endforeach' => $this->compileEndForeach(),
            'forelse' => $this->compileForelse($expression),
            'empty' => $this->compileForelseEmpty(),
            'endforelse' => $this->compileEndForelse(),
            'while' => $this->compileWhile($expression),
            'endwhile' => $this->compileEndWhile(),
            'switch' => $this->compileSwitch($expression),
            'case' => $this->compileCase($expression),
            'default' => $this->compileDefault(),
            'break' => $this->compileBreak($expression),
            'continue' => $this->compileContinue($expression),
            'endswitch' => $this->compileEndSwitch(),
            'extends' => $this->compileExtends($expression),
            'section' => $this->compileSection($expression),
            'endsection' => $this->compileEndSection(),
            'yield' => $this->compileYield($expression),
            'parent' => $this->compileParent(),
            'show' => $this->compileShow(),
            'include' => $this->compileInclude($expression),
            'includeIf' => $this->compileIncludeIf($expression),
            'includeWhen' => $this->compileIncludeWhen($expression),
            'each' => $this->compileEach($expression),
            'push' => $this->compilePush($expression),
            'endpush' => $this->compileEndPush(),
            'stack' => $this->compileStack($expression),
            'json' => $this->compileJson($expression),
            'class' => $this->compileClass($expression),
            'style' => $this->compileStyle($expression),
            'checked' => $this->compileChecked($expression),
            'selected' => $this->compileSelected($expression),
            'disabled' => $this->compileDisabled($expression),
            'readonly' => $this->compileReadonly($expression),
            'required' => $this->compileRequired($expression),
            'csrf' => $this->compileCsrf(),
            'method' => $this->compileMethod($expression),
            'auth' => $this->compileAuth($expression),
            'endauth' => $this->compileEndAuth(),
            'guest' => $this->compileGuest($expression),
            'endguest' => $this->compileEndGuest(),
            'env' => $this->compileEnv($expression),
            'endenv' => $this->compileEndEnv(),
            'production' => $this->compileProduction(),
            'endproduction' => $this->compileEndProduction(),
            'dump' => $this->compileDump($expression),
            'dd' => $this->compileDd($expression),
            'vite' => $this->compileVite($expression),
            default => $match[0],
        };
    }

    protected function compileEchos(string $value): string
    {
        // Compile raw echos {!! !!}
        $value = preg_replace_callback(
            '/\{!!\s*(.+?)\s*!!\}/',
            fn($matches) => "<?php echo {$matches[1]}; ?>",
            $value
        );

        // Compile escaped echos {{ }}
        $value = preg_replace_callback(
            '/\{\{\s*(.+?)\s*\}\}/',
            fn($matches) => "<?php echo htmlspecialchars({$matches[1]}, ENT_QUOTES, 'UTF-8'); ?>",
            $value
        );

        return $value;
    }

    // Control Structures

    protected function compileIf(string $expression): string
    {
        return "<?php if({$expression}): ?>";
    }

    protected function compileElseIf(string $expression): string
    {
        return "<?php elseif({$expression}): ?>";
    }

    protected function compileElse(): string
    {
        return '<?php else: ?>';
    }

    protected function compileEndIf(): string
    {
        return '<?php endif; ?>';
    }

    protected function compileUnless(string $expression): string
    {
        return "<?php if(!({$expression})): ?>";
    }

    protected function compileEndUnless(): string
    {
        return '<?php endif; ?>';
    }

    protected function compileIsset(string $expression): string
    {
        return "<?php if(isset({$expression})): ?>";
    }

    protected function compileEndIsset(): string
    {
        return '<?php endif; ?>';
    }

    protected function compileEmpty(string $expression): string
    {
        return "<?php if(empty({$expression})): ?>";
    }

    protected function compileEndEmpty(): string
    {
        return '<?php endif; ?>';
    }

    // Loops

    protected function compileFor(string $expression): string
    {
        return "<?php for({$expression}): ?>";
    }

    protected function compileEndFor(): string
    {
        return '<?php endfor; ?>';
    }

    protected function compileForeach(string $expression): string
    {
        return "<?php foreach({$expression}): ?>";
    }

    protected function compileEndForeach(): string
    {
        return '<?php endforeach; ?>';
    }

    protected function compileForelse(string $expression): string
    {
        preg_match('/\$(\w+)/', $expression, $matches);
        $iteratee = '$' . ($matches[1] ?? 'item');

        return "<?php \$__empty = true; foreach({$expression}): \$__empty = false; ?>";
    }

    protected function compileForelseEmpty(): string
    {
        return '<?php endforeach; if($__empty): ?>';
    }

    protected function compileEndForelse(): string
    {
        return '<?php endif; ?>';
    }

    protected function compileWhile(string $expression): string
    {
        return "<?php while({$expression}): ?>";
    }

    protected function compileEndWhile(): string
    {
        return '<?php endwhile; ?>';
    }

    protected function compileSwitch(string $expression): string
    {
        return "<?php switch({$expression}): ?>";
    }

    protected function compileCase(string $expression): string
    {
        return "<?php case {$expression}: ?>";
    }

    protected function compileDefault(): string
    {
        return '<?php default: ?>';
    }

    protected function compileBreak(string $expression): string
    {
        return $expression
            ? "<?php if({$expression}) break; ?>"
            : '<?php break; ?>';
    }

    protected function compileContinue(string $expression): string
    {
        return $expression
            ? "<?php if({$expression}) continue; ?>"
            : '<?php continue; ?>';
    }

    protected function compileEndSwitch(): string
    {
        return '<?php endswitch; ?>';
    }

    // Template Inheritance

    protected function compileExtends(string $expression): string
    {
        return "<?php \$__env->startExtends({$expression}); ?>";
    }

    protected function compileSection(string $expression): string
    {
        return "<?php \$__env->startSection({$expression}); ?>";
    }

    protected function compileEndSection(): string
    {
        return '<?php $__env->endSection(); ?>';
    }

    protected function compileYield(string $expression): string
    {
        return "<?php echo \$__env->yieldContent({$expression}); ?>";
    }

    protected function compileParent(): string
    {
        return '<?php echo $__env->yieldParent(); ?>';
    }

    protected function compileShow(): string
    {
        return '<?php echo $__env->yieldSection(); ?>';
    }

    // Includes

    protected function compileInclude(string $expression): string
    {
        return "<?php echo \$__env->make({$expression}, \Forjix\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
    }

    protected function compileIncludeIf(string $expression): string
    {
        return "<?php if(\$__env->exists({$expression})) echo \$__env->make({$expression}, \Forjix\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>";
    }

    protected function compileIncludeWhen(string $expression): string
    {
        return "<?php echo \$__env->renderWhen({$expression}, \Forjix\Support\Arr::except(get_defined_vars(), ['__data', '__path'])); ?>";
    }

    protected function compileEach(string $expression): string
    {
        return "<?php echo \$__env->renderEach({$expression}); ?>";
    }

    // Stacks

    protected function compilePush(string $expression): string
    {
        return "<?php \$__env->startPush({$expression}); ?>";
    }

    protected function compileEndPush(): string
    {
        return '<?php $__env->endPush(); ?>';
    }

    protected function compileStack(string $expression): string
    {
        return "<?php echo \$__env->yieldPushContent({$expression}); ?>";
    }

    // Helpers

    protected function compileJson(string $expression): string
    {
        $parts = explode(',', $expression);
        $default = trim($parts[1] ?? 'JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT');

        return "<?php echo json_encode({$parts[0]}, {$default}); ?>";
    }

    protected function compileClass(string $expression): string
    {
        return "<?php echo \Forjix\View\Helpers::classAttribute({$expression}); ?>";
    }

    protected function compileStyle(string $expression): string
    {
        return "<?php echo \Forjix\View\Helpers::styleAttribute({$expression}); ?>";
    }

    protected function compileChecked(string $expression): string
    {
        return "<?php echo ({$expression}) ? 'checked' : ''; ?>";
    }

    protected function compileSelected(string $expression): string
    {
        return "<?php echo ({$expression}) ? 'selected' : ''; ?>";
    }

    protected function compileDisabled(string $expression): string
    {
        return "<?php echo ({$expression}) ? 'disabled' : ''; ?>";
    }

    protected function compileReadonly(string $expression): string
    {
        return "<?php echo ({$expression}) ? 'readonly' : ''; ?>";
    }

    protected function compileRequired(string $expression): string
    {
        return "<?php echo ({$expression}) ? 'required' : ''; ?>";
    }

    protected function compileCsrf(): string
    {
        return '<input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">';
    }

    protected function compileMethod(string $expression): string
    {
        return "<input type=\"hidden\" name=\"_method\" value=\"<?php echo {$expression}; ?>\">";
    }

    // Auth

    protected function compileAuth(string $expression): string
    {
        $guard = $expression ?: 'null';
        return "<?php if(auth({$guard})->check()): ?>";
    }

    protected function compileEndAuth(): string
    {
        return '<?php endif; ?>';
    }

    protected function compileGuest(string $expression): string
    {
        $guard = $expression ?: 'null';
        return "<?php if(auth({$guard})->guest()): ?>";
    }

    protected function compileEndGuest(): string
    {
        return '<?php endif; ?>';
    }

    // Environment

    protected function compileEnv(string $expression): string
    {
        return "<?php if(app()->environment({$expression})): ?>";
    }

    protected function compileEndEnv(): string
    {
        return '<?php endif; ?>';
    }

    protected function compileProduction(): string
    {
        return "<?php if(app()->isProduction()): ?>";
    }

    protected function compileEndProduction(): string
    {
        return '<?php endif; ?>';
    }

    // Debug

    protected function compileDump(string $expression): string
    {
        return "<?php dump({$expression}); ?>";
    }

    protected function compileDd(string $expression): string
    {
        return "<?php dd({$expression}); ?>";
    }

    // Vite

    protected function compileVite(string $expression): string
    {
        return "<?php echo vite({$expression}); ?>";
    }

    // Custom Directives

    public function directive(string $name, callable $handler): void
    {
        $this->customDirectives[$name] = $handler;
    }

    public function if(string $name, callable $callback): void
    {
        $this->conditions[$name] = $callback;

        $this->directive($name, fn($expression) => "<?php if(call_user_func(\$__env->getCondition('{$name}'), {$expression})): ?>");
        $this->directive("else{$name}", fn($expression) => "<?php elseif(call_user_func(\$__env->getCondition('{$name}'), {$expression})): ?>");
        $this->directive("end{$name}", fn() => '<?php endif; ?>');
    }

    public function getCondition(string $name): ?callable
    {
        return $this->conditions[$name] ?? null;
    }
}
