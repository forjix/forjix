<?php

declare(strict_types=1);

namespace Forjix\Support\Tests;

use Forjix\Support\Arr;
use PHPUnit\Framework\TestCase;

class ArrTest extends TestCase
{
    public function testGet(): void
    {
        $array = ['name' => 'John', 'address' => ['city' => 'NYC']];

        $this->assertEquals('John', Arr::get($array, 'name'));
        $this->assertEquals('NYC', Arr::get($array, 'address.city'));
        $this->assertEquals('default', Arr::get($array, 'missing', 'default'));
        $this->assertNull(Arr::get($array, 'missing'));
    }

    public function testSet(): void
    {
        $array = [];

        Arr::set($array, 'name', 'John');
        $this->assertEquals(['name' => 'John'], $array);

        Arr::set($array, 'address.city', 'NYC');
        $this->assertEquals('NYC', $array['address']['city']);
    }

    public function testHas(): void
    {
        $array = ['name' => 'John', 'address' => ['city' => 'NYC']];

        $this->assertTrue(Arr::has($array, 'name'));
        $this->assertTrue(Arr::has($array, 'address.city'));
        $this->assertFalse(Arr::has($array, 'missing'));
        $this->assertFalse(Arr::has($array, 'address.country'));
    }

    public function testForget(): void
    {
        $array = ['name' => 'John', 'age' => 30];

        Arr::forget($array, 'age');
        $this->assertEquals(['name' => 'John'], $array);
    }

    public function testOnly(): void
    {
        $array = ['name' => 'John', 'age' => 30, 'city' => 'NYC'];

        $this->assertEquals(['name' => 'John', 'age' => 30], Arr::only($array, ['name', 'age']));
    }

    public function testExcept(): void
    {
        $array = ['name' => 'John', 'age' => 30, 'city' => 'NYC'];

        $this->assertEquals(['city' => 'NYC'], Arr::except($array, ['name', 'age']));
    }

    public function testFirst(): void
    {
        $array = [1, 2, 3, 4, 5];

        $this->assertEquals(1, Arr::first($array));
        $this->assertEquals(4, Arr::first($array, fn($v) => $v > 3));
        $this->assertEquals('default', Arr::first([], null, 'default'));
    }

    public function testLast(): void
    {
        $array = [1, 2, 3, 4, 5];

        $this->assertEquals(5, Arr::last($array));
        $this->assertEquals(3, Arr::last($array, fn($v) => $v < 4));
    }

    public function testFlatten(): void
    {
        $array = [1, [2, 3], [[4, 5]]];

        $this->assertEquals([1, 2, 3, 4, 5], Arr::flatten($array));
        $this->assertEquals([1, 2, 3, [4, 5]], Arr::flatten($array, 1));
    }

    public function testDot(): void
    {
        $array = ['user' => ['name' => 'John', 'address' => ['city' => 'NYC']]];

        $this->assertEquals([
            'user.name' => 'John',
            'user.address.city' => 'NYC',
        ], Arr::dot($array));
    }

    public function testWrap(): void
    {
        $this->assertEquals(['value'], Arr::wrap('value'));
        $this->assertEquals([1, 2], Arr::wrap([1, 2]));
        $this->assertEquals([], Arr::wrap(null));
    }

    public function testPluck(): void
    {
        $array = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ];

        $this->assertEquals(['John', 'Jane'], Arr::pluck($array, 'name'));
        $this->assertEquals([1 => 'John', 2 => 'Jane'], Arr::pluck($array, 'name', 'id'));
    }

    public function testGroupBy(): void
    {
        $array = [
            ['type' => 'a', 'value' => 1],
            ['type' => 'b', 'value' => 2],
            ['type' => 'a', 'value' => 3],
        ];

        $grouped = Arr::groupBy($array, 'type');

        $this->assertCount(2, $grouped['a']);
        $this->assertCount(1, $grouped['b']);
    }
}
