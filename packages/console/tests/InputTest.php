<?php

declare(strict_types=1);

namespace Forjix\Console\Tests;

use Forjix\Console\Input;
use PHPUnit\Framework\TestCase;

class InputTest extends TestCase
{
    public function testArguments(): void
    {
        $input = new Input(['script.php', 'arg1', 'arg2']);

        $this->assertEquals('arg1', $input->argument(0));
        $this->assertEquals('arg2', $input->argument(1));
        $this->assertNull($input->argument(2));
    }

    public function testArgumentWithDefault(): void
    {
        $input = new Input(['script.php']);

        $this->assertEquals('default', $input->argument(0, 'default'));
    }

    public function testAllArguments(): void
    {
        $input = new Input(['script.php', 'arg1', 'arg2']);

        $args = $input->arguments();

        $this->assertCount(2, $args);
        $this->assertEquals('arg1', $args[0]);
        $this->assertEquals('arg2', $args[1]);
    }

    public function testOptions(): void
    {
        $input = new Input(['script.php', '--name=John', '--verbose']);

        $this->assertEquals('John', $input->option('name'));
        $this->assertTrue($input->option('verbose'));
        $this->assertNull($input->option('missing'));
    }

    public function testOptionWithDefault(): void
    {
        $input = new Input(['script.php']);

        $this->assertEquals('default', $input->option('name', 'default'));
    }

    public function testShortOptions(): void
    {
        $input = new Input(['script.php', '-v', '-n=John']);

        $this->assertTrue($input->option('v'));
        $this->assertEquals('John', $input->option('n'));
    }

    public function testAllOptions(): void
    {
        $input = new Input(['script.php', '--name=John', '--verbose']);

        $options = $input->options();

        $this->assertArrayHasKey('name', $options);
        $this->assertArrayHasKey('verbose', $options);
    }

    public function testHasOption(): void
    {
        $input = new Input(['script.php', '--verbose']);

        $this->assertTrue($input->hasOption('verbose'));
        $this->assertFalse($input->hasOption('quiet'));
    }

    public function testMixedArgumentsAndOptions(): void
    {
        $input = new Input(['script.php', 'command', '--verbose', 'arg1', '--name=John']);

        $args = $input->arguments();
        $options = $input->options();

        $this->assertEquals('command', $args[0]);
        $this->assertEquals('arg1', $args[1]);
        $this->assertTrue($options['verbose']);
        $this->assertEquals('John', $options['name']);
    }

    public function testCommand(): void
    {
        $input = new Input(['script.php', 'migrate:run']);

        $this->assertEquals('migrate:run', $input->command());
    }

    public function testEmptyCommand(): void
    {
        $input = new Input(['script.php']);

        $this->assertNull($input->command());
    }
}
