<?php

declare(strict_types=1);

namespace Forjix\Core\Tests;

use Forjix\Core\Container\Container;
use Forjix\Core\Container\NotFoundException;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testBindAndResolve(): void
    {
        $this->container->bind('foo', fn() => 'bar');

        $this->assertEquals('bar', $this->container->make('foo'));
    }

    public function testSingleton(): void
    {
        $this->container->singleton('counter', fn() => new \stdClass());

        $first = $this->container->make('counter');
        $second = $this->container->make('counter');

        $this->assertSame($first, $second);
    }

    public function testInstance(): void
    {
        $instance = new \stdClass();
        $instance->name = 'test';

        $this->container->instance('foo', $instance);

        $this->assertSame($instance, $this->container->make('foo'));
    }

    public function testAlias(): void
    {
        $this->container->bind('original', fn() => 'value');
        $this->container->alias('original', 'aliased');

        $this->assertEquals('value', $this->container->make('aliased'));
    }

    public function testBound(): void
    {
        $this->assertFalse($this->container->bound('foo'));

        $this->container->bind('foo', fn() => 'bar');

        $this->assertTrue($this->container->bound('foo'));
    }

    public function testHas(): void
    {
        $this->assertFalse($this->container->has('foo'));

        $this->container->bind('foo', fn() => 'bar');

        $this->assertTrue($this->container->has('foo'));
    }

    public function testGet(): void
    {
        $this->container->bind('foo', fn() => 'bar');

        $this->assertEquals('bar', $this->container->get('foo'));
    }

    public function testGetThrowsNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);

        $this->container->get('nonexistent');
    }

    public function testAutoWiring(): void
    {
        $instance = $this->container->make(SimpleClass::class);

        $this->assertInstanceOf(SimpleClass::class, $instance);
    }

    public function testAutoWiringWithDependencies(): void
    {
        $instance = $this->container->make(ClassWithDependency::class);

        $this->assertInstanceOf(ClassWithDependency::class, $instance);
        $this->assertInstanceOf(SimpleClass::class, $instance->dependency);
    }

    public function testMakeWithParameters(): void
    {
        $instance = $this->container->make(ClassWithScalarDependency::class, ['value' => 'test']);

        $this->assertEquals('test', $instance->value);
    }

    public function testCall(): void
    {
        $result = $this->container->call(fn() => 'called');

        $this->assertEquals('called', $result);
    }

    public function testCallWithDependencies(): void
    {
        $result = $this->container->call(function (SimpleClass $simple) {
            return $simple;
        });

        $this->assertInstanceOf(SimpleClass::class, $result);
    }

    public function testFlush(): void
    {
        $this->container->bind('foo', fn() => 'bar');
        $this->container->flush();

        $this->assertFalse($this->container->bound('foo'));
    }

    public function testGetInstance(): void
    {
        Container::setInstance(null);
        $instance = Container::getInstance();

        $this->assertInstanceOf(Container::class, $instance);
        $this->assertSame($instance, Container::getInstance());
    }
}

class SimpleClass
{
    public function __construct()
    {
    }
}

class ClassWithDependency
{
    public function __construct(public SimpleClass $dependency)
    {
    }
}

class ClassWithScalarDependency
{
    public function __construct(public string $value = 'default')
    {
    }
}
