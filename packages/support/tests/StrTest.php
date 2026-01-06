<?php

declare(strict_types=1);

namespace Forjix\Support\Tests;

use Forjix\Support\Str;
use PHPUnit\Framework\TestCase;

class StrTest extends TestCase
{
    public function testCamel(): void
    {
        $this->assertEquals('helloWorld', Str::camel('hello_world'));
        $this->assertEquals('helloWorld', Str::camel('hello-world'));
        $this->assertEquals('helloWorld', Str::camel('HelloWorld'));
    }

    public function testSnake(): void
    {
        $this->assertEquals('hello_world', Str::snake('helloWorld'));
        $this->assertEquals('hello_world', Str::snake('HelloWorld'));
    }

    public function testKebab(): void
    {
        $this->assertEquals('hello-world', Str::kebab('helloWorld'));
        $this->assertEquals('hello-world', Str::kebab('HelloWorld'));
    }

    public function testStudly(): void
    {
        $this->assertEquals('HelloWorld', Str::studly('hello_world'));
        $this->assertEquals('HelloWorld', Str::studly('hello-world'));
    }

    public function testSlug(): void
    {
        $this->assertEquals('hello-world', Str::slug('Hello World'));
        $this->assertEquals('hello_world', Str::slug('Hello World', '_'));
    }

    public function testContains(): void
    {
        $this->assertTrue(Str::contains('Hello World', 'World'));
        $this->assertFalse(Str::contains('Hello World', 'world'));
        $this->assertTrue(Str::contains('Hello World', 'world', true));
    }

    public function testStartsWith(): void
    {
        $this->assertTrue(Str::startsWith('Hello World', 'Hello'));
        $this->assertFalse(Str::startsWith('Hello World', 'World'));
    }

    public function testEndsWith(): void
    {
        $this->assertTrue(Str::endsWith('Hello World', 'World'));
        $this->assertFalse(Str::endsWith('Hello World', 'Hello'));
    }

    public function testBefore(): void
    {
        $this->assertEquals('Hello', Str::before('Hello World', ' '));
        $this->assertEquals('Hello World', Str::before('Hello World', '-'));
    }

    public function testAfter(): void
    {
        $this->assertEquals('World', Str::after('Hello World', ' '));
        $this->assertEquals('Hello World', Str::after('Hello World', '-'));
    }

    public function testLimit(): void
    {
        $this->assertEquals('Hello...', Str::limit('Hello World', 5));
        $this->assertEquals('Hello World', Str::limit('Hello World', 20));
    }

    public function testRandom(): void
    {
        $random = Str::random(16);
        $this->assertEquals(16, strlen($random));
    }

    public function testUuid(): void
    {
        $uuid = Str::uuid();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuid);
    }

    public function testMask(): void
    {
        $this->assertEquals('Hel***', Str::mask('Hello!', '*', 3));
        $this->assertEquals('***lo!', Str::mask('Hello!', '*', 0, 3));
    }

    public function testIs(): void
    {
        $this->assertTrue(Str::is('foo*', 'foobar'));
        $this->assertTrue(Str::is('foo', 'foo'));
        $this->assertFalse(Str::is('foo*', 'bar'));
    }

    public function testSquish(): void
    {
        $this->assertEquals('Hello World', Str::squish('  Hello   World  '));
    }
}
