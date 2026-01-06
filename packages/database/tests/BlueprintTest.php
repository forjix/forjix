<?php

declare(strict_types=1);

namespace Forjix\Database\Tests;

use Forjix\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;

class BlueprintTest extends TestCase
{
    protected Blueprint $blueprint;

    protected function setUp(): void
    {
        $this->blueprint = new Blueprint('users');
    }

    public function testId(): void
    {
        $this->blueprint->id();
        $columns = $this->blueprint->getColumns();

        $this->assertCount(1, $columns);
        $this->assertEquals('id', $columns[0]['name']);
        $this->assertEquals('bigInteger', $columns[0]['type']);
        $this->assertTrue($columns[0]['autoIncrement']);
        $this->assertTrue($columns[0]['primaryKey']);
    }

    public function testString(): void
    {
        $this->blueprint->string('name', 100);
        $columns = $this->blueprint->getColumns();

        $this->assertEquals('name', $columns[0]['name']);
        $this->assertEquals('string', $columns[0]['type']);
        $this->assertEquals(100, $columns[0]['length']);
    }

    public function testText(): void
    {
        $this->blueprint->text('description');
        $columns = $this->blueprint->getColumns();

        $this->assertEquals('description', $columns[0]['name']);
        $this->assertEquals('text', $columns[0]['type']);
    }

    public function testInteger(): void
    {
        $this->blueprint->integer('age');
        $columns = $this->blueprint->getColumns();

        $this->assertEquals('age', $columns[0]['name']);
        $this->assertEquals('integer', $columns[0]['type']);
    }

    public function testBoolean(): void
    {
        $this->blueprint->boolean('active');
        $columns = $this->blueprint->getColumns();

        $this->assertEquals('active', $columns[0]['name']);
        $this->assertEquals('boolean', $columns[0]['type']);
    }

    public function testTimestamp(): void
    {
        $this->blueprint->timestamp('created_at');
        $columns = $this->blueprint->getColumns();

        $this->assertEquals('created_at', $columns[0]['name']);
        $this->assertEquals('timestamp', $columns[0]['type']);
    }

    public function testTimestamps(): void
    {
        $this->blueprint->timestamps();
        $columns = $this->blueprint->getColumns();

        $this->assertCount(2, $columns);
        $this->assertEquals('created_at', $columns[0]['name']);
        $this->assertEquals('updated_at', $columns[1]['name']);
    }

    public function testNullable(): void
    {
        $this->blueprint->string('nickname')->nullable();
        $columns = $this->blueprint->getColumns();

        $this->assertTrue($columns[0]['nullable']);
    }

    public function testDefault(): void
    {
        $this->blueprint->boolean('active')->default(true);
        $columns = $this->blueprint->getColumns();

        $this->assertEquals(true, $columns[0]['default']);
    }

    public function testUnique(): void
    {
        $this->blueprint->string('email')->unique();
        $columns = $this->blueprint->getColumns();

        $this->assertTrue($columns[0]['unique']);
    }

    public function testIndex(): void
    {
        $this->blueprint->string('name')->index();
        $columns = $this->blueprint->getColumns();

        $this->assertTrue($columns[0]['index']);
    }

    public function testForeignId(): void
    {
        $this->blueprint->foreignId('user_id');
        $columns = $this->blueprint->getColumns();

        $this->assertEquals('user_id', $columns[0]['name']);
        $this->assertEquals('bigInteger', $columns[0]['type']);
        $this->assertTrue($columns[0]['unsigned']);
    }

    public function testJson(): void
    {
        $this->blueprint->json('options');
        $columns = $this->blueprint->getColumns();

        $this->assertEquals('options', $columns[0]['name']);
        $this->assertEquals('json', $columns[0]['type']);
    }

    public function testDecimal(): void
    {
        $this->blueprint->decimal('price', 8, 2);
        $columns = $this->blueprint->getColumns();

        $this->assertEquals('price', $columns[0]['name']);
        $this->assertEquals('decimal', $columns[0]['type']);
        $this->assertEquals(8, $columns[0]['precision']);
        $this->assertEquals(2, $columns[0]['scale']);
    }
}
