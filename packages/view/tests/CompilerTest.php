<?php

declare(strict_types=1);

namespace Forjix\View\Tests;

use Forjix\View\Compiler;
use PHPUnit\Framework\TestCase;

class CompilerTest extends TestCase
{
    protected Compiler $compiler;

    protected function setUp(): void
    {
        $this->compiler = new Compiler();
    }

    public function testEchoStatements(): void
    {
        $template = '{{ $name }}';
        $compiled = $this->compiler->compile($template);

        $this->assertStringContainsString('htmlspecialchars', $compiled);
        $this->assertStringContainsString('$name', $compiled);
    }

    public function testRawEchoStatements(): void
    {
        $template = '{!! $html !!}';
        $compiled = $this->compiler->compile($template);

        $this->assertStringNotContainsString('htmlspecialchars', $compiled);
        $this->assertStringContainsString('$html', $compiled);
    }

    public function testIfStatements(): void
    {
        $template = '@if($condition) Yes @endif';
        $compiled = $this->compiler->compile($template);

        $this->assertStringContainsString('if', $compiled);
        $this->assertStringContainsString('$condition', $compiled);
        $this->assertStringContainsString('endif', $compiled);
    }

    public function testIfElseStatements(): void
    {
        $template = '@if($condition) Yes @else No @endif';
        $compiled = $this->compiler->compile($template);

        $this->assertStringContainsString('if', $compiled);
        $this->assertStringContainsString('else', $compiled);
    }

    public function testElseIfStatements(): void
    {
        $template = '@if($a) A @elseif($b) B @else C @endif';
        $compiled = $this->compiler->compile($template);

        $this->assertStringContainsString('elseif', $compiled);
    }

    public function testForeachStatements(): void
    {
        $template = '@foreach($items as $item) {{ $item }} @endforeach';
        $compiled = $this->compiler->compile($template);

        $this->assertStringContainsString('foreach', $compiled);
        $this->assertStringContainsString('$items', $compiled);
        $this->assertStringContainsString('$item', $compiled);
        $this->assertStringContainsString('endforeach', $compiled);
    }

    public function testForStatements(): void
    {
        $template = '@for($i = 0; $i < 10; $i++) {{ $i }} @endfor';
        $compiled = $this->compiler->compile($template);

        $this->assertStringContainsString('for', $compiled);
        $this->assertStringContainsString('endfor', $compiled);
    }

    public function testWhileStatements(): void
    {
        $template = '@while($condition) Loop @endwhile';
        $compiled = $this->compiler->compile($template);

        $this->assertStringContainsString('while', $compiled);
        $this->assertStringContainsString('endwhile', $compiled);
    }

    public function testIssetStatements(): void
    {
        $template = '@isset($variable) Set @endisset';
        $compiled = $this->compiler->compile($template);

        $this->assertStringContainsString('isset', $compiled);
    }

    public function testEmptyStatements(): void
    {
        $template = '@empty($items) Empty @endempty';
        $compiled = $this->compiler->compile($template);

        $this->assertStringContainsString('empty', $compiled);
    }

    public function testUnlessStatements(): void
    {
        $template = '@unless($condition) Show @endunless';
        $compiled = $this->compiler->compile($template);

        $this->assertStringContainsString('if', $compiled);
        $this->assertStringContainsString('!', $compiled);
    }

    public function testComments(): void
    {
        $template = '{{-- This is a comment --}}';
        $compiled = $this->compiler->compile($template);

        $this->assertStringNotContainsString('This is a comment', $compiled);
    }

    public function testPhpStatements(): void
    {
        $template = '@php $x = 1; @endphp';
        $compiled = $this->compiler->compile($template);

        $this->assertStringContainsString('$x = 1', $compiled);
    }

    public function testIncludeStatements(): void
    {
        $template = "@include('partials.header')";
        $compiled = $this->compiler->compile($template);

        $this->assertStringContainsString('include', $compiled);
        $this->assertStringContainsString('partials.header', $compiled);
    }

    public function testExtendsStatements(): void
    {
        $template = "@extends('layouts.app')";
        $compiled = $this->compiler->compile($template);

        $this->assertStringContainsString('layouts.app', $compiled);
    }

    public function testSectionStatements(): void
    {
        $template = "@section('content') Content here @endsection";
        $compiled = $this->compiler->compile($template);

        $this->assertStringContainsString('content', $compiled);
    }

    public function testYieldStatements(): void
    {
        $template = "@yield('content')";
        $compiled = $this->compiler->compile($template);

        $this->assertStringContainsString('content', $compiled);
    }
}
