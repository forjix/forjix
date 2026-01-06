<?php

declare(strict_types=1);

namespace Forjix\Support\Tests;

use Forjix\Support\Collection;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    public function testMake(): void
    {
        $collection = Collection::make([1, 2, 3]);
        $this->assertCount(3, $collection);
    }

    public function testAll(): void
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertEquals([1, 2, 3], $collection->all());
    }

    public function testFirst(): void
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertEquals(1, $collection->first());
        $this->assertEquals(2, $collection->first(fn($v) => $v > 1));
    }

    public function testLast(): void
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertEquals(3, $collection->last());
    }

    public function testMap(): void
    {
        $collection = new Collection([1, 2, 3]);
        $mapped = $collection->map(fn($v) => $v * 2);

        $this->assertEquals([2, 4, 6], $mapped->values()->all());
    }

    public function testFilter(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $filtered = $collection->filter(fn($v) => $v > 2);

        $this->assertEquals([3, 4, 5], $filtered->values()->all());
    }

    public function testReduce(): void
    {
        $collection = new Collection([1, 2, 3]);
        $sum = $collection->reduce(fn($carry, $item) => $carry + $item, 0);

        $this->assertEquals(6, $sum);
    }

    public function testPluck(): void
    {
        $collection = new Collection([
            ['name' => 'John'],
            ['name' => 'Jane'],
        ]);

        $this->assertEquals(['John', 'Jane'], $collection->pluck('name')->all());
    }

    public function testChunk(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $chunks = $collection->chunk(2);

        $this->assertCount(3, $chunks);
    }

    public function testContains(): void
    {
        $collection = new Collection([1, 2, 3]);

        $this->assertTrue($collection->contains(2));
        $this->assertFalse($collection->contains(5));
    }

    public function testUnique(): void
    {
        $collection = new Collection([1, 2, 2, 3, 3, 3]);
        $unique = $collection->unique();

        $this->assertEquals([1, 2, 3], $unique->values()->all());
    }

    public function testMerge(): void
    {
        $collection = new Collection([1, 2]);
        $merged = $collection->merge([3, 4]);

        $this->assertEquals([1, 2, 3, 4], $merged->all());
    }

    public function testSum(): void
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertEquals(6, $collection->sum());
    }

    public function testAvg(): void
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertEquals(2, $collection->avg());
    }

    public function testMinMax(): void
    {
        $collection = new Collection([1, 2, 3]);

        $this->assertEquals(1, $collection->min());
        $this->assertEquals(3, $collection->max());
    }

    public function testToJson(): void
    {
        $collection = new Collection(['name' => 'John']);
        $this->assertEquals('{"name":"John"}', $collection->toJson());
    }

    public function testIsEmpty(): void
    {
        $this->assertTrue((new Collection([]))->isEmpty());
        $this->assertFalse((new Collection([1]))->isEmpty());
    }

    public function testGroupBy(): void
    {
        $collection = new Collection([
            ['type' => 'a', 'value' => 1],
            ['type' => 'b', 'value' => 2],
            ['type' => 'a', 'value' => 3],
        ]);

        $grouped = $collection->groupBy('type');

        $this->assertCount(2, $grouped->get('a'));
        $this->assertCount(1, $grouped->get('b'));
    }

    public function testSortBy(): void
    {
        $collection = new Collection([
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
        ]);

        $sorted = $collection->sortBy('age');
        $this->assertEquals('Jane', $sorted->first()['name']);
    }
}
