<?php

declare(strict_types=1);

namespace Forjix\Console\Tests;

use Forjix\Console\Output;
use PHPUnit\Framework\TestCase;

class OutputTest extends TestCase
{
    protected Output $output;

    protected function setUp(): void
    {
        $this->output = new Output();
    }

    public function testLine(): void
    {
        ob_start();
        $this->output->line('Hello World');
        $output = ob_get_clean();

        $this->assertStringContainsString('Hello World', $output);
    }

    public function testInfo(): void
    {
        ob_start();
        $this->output->info('Info message');
        $output = ob_get_clean();

        $this->assertStringContainsString('Info message', $output);
    }

    public function testSuccess(): void
    {
        ob_start();
        $this->output->success('Success message');
        $output = ob_get_clean();

        $this->assertStringContainsString('Success message', $output);
    }

    public function testWarning(): void
    {
        ob_start();
        $this->output->warning('Warning message');
        $output = ob_get_clean();

        $this->assertStringContainsString('Warning message', $output);
    }

    public function testError(): void
    {
        ob_start();
        $this->output->error('Error message');
        $output = ob_get_clean();

        $this->assertStringContainsString('Error message', $output);
    }

    public function testNewLine(): void
    {
        ob_start();
        $this->output->newLine(2);
        $output = ob_get_clean();

        $this->assertEquals("\n\n", $output);
    }

    public function testTable(): void
    {
        ob_start();
        $this->output->table(
            ['Name', 'Email'],
            [
                ['John', 'john@example.com'],
                ['Jane', 'jane@example.com'],
            ]
        );
        $output = ob_get_clean();

        $this->assertStringContainsString('Name', $output);
        $this->assertStringContainsString('Email', $output);
        $this->assertStringContainsString('John', $output);
        $this->assertStringContainsString('jane@example.com', $output);
    }
}
