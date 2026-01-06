<?php

declare(strict_types=1);

namespace Forjix\Database\Tests;

use Forjix\Database\QueryBuilder;
use PHPUnit\Framework\TestCase;
use PDO;

class QueryBuilderTest extends TestCase
{
    protected QueryBuilder $builder;
    protected PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, age INTEGER, active INTEGER)');
        $this->pdo->exec("INSERT INTO users (name, email, age, active) VALUES ('John', 'john@example.com', 30, 1)");
        $this->pdo->exec("INSERT INTO users (name, email, age, active) VALUES ('Jane', 'jane@example.com', 25, 1)");
        $this->pdo->exec("INSERT INTO users (name, email, age, active) VALUES ('Bob', 'bob@example.com', 35, 0)");

        $this->builder = new QueryBuilder($this->pdo, 'users');
    }

    public function testSelect(): void
    {
        $results = $this->builder->select('name', 'email')->get();

        $this->assertCount(3, $results);
        $this->assertArrayHasKey('name', $results[0]);
        $this->assertArrayHasKey('email', $results[0]);
    }

    public function testWhere(): void
    {
        $results = $this->builder->where('name', 'John')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('John', $results[0]['name']);
    }

    public function testWhereWithOperator(): void
    {
        $results = $this->builder->where('age', '>', 28)->get();

        $this->assertCount(2, $results);
    }

    public function testOrWhere(): void
    {
        $results = $this->builder->where('name', 'John')->orWhere('name', 'Jane')->get();

        $this->assertCount(2, $results);
    }

    public function testWhereIn(): void
    {
        $results = $this->builder->whereIn('name', ['John', 'Jane'])->get();

        $this->assertCount(2, $results);
    }

    public function testWhereNull(): void
    {
        $this->pdo->exec("INSERT INTO users (name, email, age, active) VALUES ('Test', NULL, 20, 1)");
        $builder = new QueryBuilder($this->pdo, 'users');

        $results = $builder->whereNull('email')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Test', $results[0]['name']);
    }

    public function testWhereNotNull(): void
    {
        $results = $this->builder->whereNotNull('email')->get();

        $this->assertCount(3, $results);
    }

    public function testOrderBy(): void
    {
        $results = $this->builder->orderBy('age', 'asc')->get();

        $this->assertEquals('Jane', $results[0]['name']);
    }

    public function testOrderByDesc(): void
    {
        $results = $this->builder->orderBy('age', 'desc')->get();

        $this->assertEquals('Bob', $results[0]['name']);
    }

    public function testLimit(): void
    {
        $results = $this->builder->limit(2)->get();

        $this->assertCount(2, $results);
    }

    public function testOffset(): void
    {
        $results = $this->builder->limit(2)->offset(1)->get();

        $this->assertCount(2, $results);
        $this->assertEquals('Jane', $results[0]['name']);
    }

    public function testFirst(): void
    {
        $result = $this->builder->where('name', 'John')->first();

        $this->assertEquals('John', $result['name']);
    }

    public function testFirstReturnsNullWhenNotFound(): void
    {
        $result = $this->builder->where('name', 'NotExist')->first();

        $this->assertNull($result);
    }

    public function testCount(): void
    {
        $count = $this->builder->count();

        $this->assertEquals(3, $count);
    }

    public function testCountWithWhere(): void
    {
        $count = $this->builder->where('active', 1)->count();

        $this->assertEquals(2, $count);
    }

    public function testInsert(): void
    {
        $builder = new QueryBuilder($this->pdo, 'users');
        $result = $builder->insert([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'age' => 28,
            'active' => 1,
        ]);

        $this->assertTrue($result);

        $builder = new QueryBuilder($this->pdo, 'users');
        $this->assertEquals(4, $builder->count());
    }

    public function testUpdate(): void
    {
        $builder = new QueryBuilder($this->pdo, 'users');
        $builder->where('name', 'John')->update(['age' => 31]);

        $builder = new QueryBuilder($this->pdo, 'users');
        $user = $builder->where('name', 'John')->first();

        $this->assertEquals(31, $user['age']);
    }

    public function testDelete(): void
    {
        $builder = new QueryBuilder($this->pdo, 'users');
        $builder->where('name', 'Bob')->delete();

        $builder = new QueryBuilder($this->pdo, 'users');
        $this->assertEquals(2, $builder->count());
    }

    public function testPluck(): void
    {
        $names = $this->builder->pluck('name');

        $this->assertCount(3, $names);
        $this->assertContains('John', $names);
    }

    public function testExists(): void
    {
        $this->assertTrue($this->builder->where('name', 'John')->exists());

        $builder = new QueryBuilder($this->pdo, 'users');
        $this->assertFalse($builder->where('name', 'NotExist')->exists());
    }
}
