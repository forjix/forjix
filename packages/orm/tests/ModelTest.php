<?php

declare(strict_types=1);

namespace Forjix\Orm\Tests;

use Forjix\Orm\Model;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{
    public function testFillable(): void
    {
        $model = new TestUser(['name' => 'John', 'email' => 'john@example.com', 'admin' => true]);

        $this->assertEquals('John', $model->name);
        $this->assertEquals('john@example.com', $model->email);
        $this->assertNull($model->admin ?? null);
    }

    public function testHidden(): void
    {
        $model = new TestUser(['name' => 'John', 'email' => 'john@example.com']);
        $model->password = 'secret';

        $array = $model->toArray();

        $this->assertArrayNotHasKey('password', $array);
    }

    public function testCasts(): void
    {
        $model = new TestUser();
        $model->active = '1';

        $this->assertTrue($model->active);
    }

    public function testGetAttribute(): void
    {
        $model = new TestUser(['name' => 'John']);

        $this->assertEquals('John', $model->getAttribute('name'));
        $this->assertNull($model->getAttribute('nonexistent'));
    }

    public function testSetAttribute(): void
    {
        $model = new TestUser();
        $model->setAttribute('name', 'John');

        $this->assertEquals('John', $model->name);
    }

    public function testIsDirty(): void
    {
        $model = new TestUser(['name' => 'John']);
        $model->syncOriginal();

        $this->assertFalse($model->isDirty());

        $model->name = 'Jane';
        $this->assertTrue($model->isDirty());
        $this->assertTrue($model->isDirty('name'));
    }

    public function testGetDirty(): void
    {
        $model = new TestUser(['name' => 'John', 'email' => 'john@example.com']);
        $model->syncOriginal();

        $model->name = 'Jane';

        $dirty = $model->getDirty();

        $this->assertArrayHasKey('name', $dirty);
        $this->assertArrayNotHasKey('email', $dirty);
    }

    public function testToArray(): void
    {
        $model = new TestUser(['name' => 'John', 'email' => 'john@example.com']);

        $array = $model->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('John', $array['name']);
    }

    public function testToJson(): void
    {
        $model = new TestUser(['name' => 'John']);

        $json = $model->toJson();

        $this->assertJson($json);
        $this->assertStringContainsString('John', $json);
    }

    public function testGetTable(): void
    {
        $model = new TestUser();

        $this->assertEquals('test_users', $model->getTable());
    }

    public function testGetPrimaryKey(): void
    {
        $model = new TestUser();

        $this->assertEquals('id', $model->getPrimaryKey());
    }

    public function testMagicGetSet(): void
    {
        $model = new TestUser();

        $model->name = 'John';
        $this->assertEquals('John', $model->name);
    }

    public function testMagicIsset(): void
    {
        $model = new TestUser(['name' => 'John']);

        $this->assertTrue(isset($model->name));
        $this->assertFalse(isset($model->nonexistent));
    }

    public function testMagicUnset(): void
    {
        $model = new TestUser(['name' => 'John']);

        unset($model->name);

        $this->assertNull($model->name);
    }

    public function testFresh(): void
    {
        $model = new TestUser(['name' => 'John']);
        $model->syncOriginal();

        $this->assertEquals('John', $model->getOriginal('name'));
    }

    public function testArrayAccess(): void
    {
        $model = new TestUser(['name' => 'John']);

        $this->assertEquals('John', $model['name']);

        $model['email'] = 'john@example.com';
        $this->assertEquals('john@example.com', $model['email']);

        $this->assertTrue(isset($model['name']));

        unset($model['name']);
        $this->assertNull($model['name']);
    }
}

class TestUser extends Model
{
    protected string $table = 'test_users';

    protected array $fillable = ['name', 'email'];

    protected array $hidden = ['password'];

    protected array $casts = [
        'active' => 'boolean',
    ];
}
