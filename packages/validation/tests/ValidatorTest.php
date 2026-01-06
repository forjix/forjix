<?php

declare(strict_types=1);

namespace Forjix\Validation\Tests;

use Forjix\Validation\ValidationException;
use Forjix\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function testRequiredRule(): void
    {
        $validator = new Validator(['name' => 'John'], ['name' => 'required']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['name' => ''], ['name' => 'required']);
        $this->assertTrue($validator->fails());

        $validator = new Validator([], ['name' => 'required']);
        $this->assertTrue($validator->fails());
    }

    public function testStringRule(): void
    {
        $validator = new Validator(['name' => 'John'], ['name' => 'string']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['name' => 123], ['name' => 'string']);
        $this->assertTrue($validator->fails());
    }

    public function testIntegerRule(): void
    {
        $validator = new Validator(['age' => 25], ['age' => 'integer']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['age' => '25'], ['age' => 'integer']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['age' => 'abc'], ['age' => 'integer']);
        $this->assertTrue($validator->fails());
    }

    public function testEmailRule(): void
    {
        $validator = new Validator(['email' => 'john@example.com'], ['email' => 'email']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['email' => 'invalid'], ['email' => 'email']);
        $this->assertTrue($validator->fails());
    }

    public function testMinRule(): void
    {
        $validator = new Validator(['name' => 'John'], ['name' => 'min:3']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['name' => 'Jo'], ['name' => 'min:3']);
        $this->assertTrue($validator->fails());

        $validator = new Validator(['age' => 25], ['age' => 'min:18']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['age' => 15], ['age' => 'min:18']);
        $this->assertTrue($validator->fails());
    }

    public function testMaxRule(): void
    {
        $validator = new Validator(['name' => 'John'], ['name' => 'max:10']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['name' => 'JohnJohnJohn'], ['name' => 'max:10']);
        $this->assertTrue($validator->fails());
    }

    public function testBetweenRule(): void
    {
        $validator = new Validator(['age' => 25], ['age' => 'between:18,65']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['age' => 15], ['age' => 'between:18,65']);
        $this->assertTrue($validator->fails());
    }

    public function testInRule(): void
    {
        $validator = new Validator(['status' => 'active'], ['status' => 'in:active,inactive']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['status' => 'deleted'], ['status' => 'in:active,inactive']);
        $this->assertTrue($validator->fails());
    }

    public function testConfirmedRule(): void
    {
        $validator = new Validator(
            ['password' => 'secret', 'password_confirmation' => 'secret'],
            ['password' => 'confirmed']
        );
        $this->assertTrue($validator->passes());

        $validator = new Validator(
            ['password' => 'secret', 'password_confirmation' => 'different'],
            ['password' => 'confirmed']
        );
        $this->assertTrue($validator->fails());
    }

    public function testNullableRule(): void
    {
        $validator = new Validator(['name' => null], ['name' => 'nullable|string']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['name' => ''], ['name' => 'nullable|string']);
        $this->assertTrue($validator->passes());
    }

    public function testUrlRule(): void
    {
        $validator = new Validator(['website' => 'https://example.com'], ['website' => 'url']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['website' => 'not-a-url'], ['website' => 'url']);
        $this->assertTrue($validator->fails());
    }

    public function testBooleanRule(): void
    {
        $validator = new Validator(['active' => true], ['active' => 'boolean']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['active' => '1'], ['active' => 'boolean']);
        $this->assertTrue($validator->passes());
    }

    public function testArrayRule(): void
    {
        $validator = new Validator(['items' => [1, 2, 3]], ['items' => 'array']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['items' => 'string'], ['items' => 'array']);
        $this->assertTrue($validator->fails());
    }

    public function testDateRule(): void
    {
        $validator = new Validator(['date' => '2024-01-01'], ['date' => 'date']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['date' => 'invalid'], ['date' => 'date']);
        $this->assertTrue($validator->fails());
    }

    public function testAlphaRule(): void
    {
        $validator = new Validator(['name' => 'John'], ['name' => 'alpha']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['name' => 'John123'], ['name' => 'alpha']);
        $this->assertTrue($validator->fails());
    }

    public function testAlphaNumRule(): void
    {
        $validator = new Validator(['code' => 'ABC123'], ['code' => 'alpha_num']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['code' => 'ABC-123'], ['code' => 'alpha_num']);
        $this->assertTrue($validator->fails());
    }

    public function testUuidRule(): void
    {
        $validator = new Validator(['id' => '550e8400-e29b-41d4-a716-446655440000'], ['id' => 'uuid']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['id' => 'not-a-uuid'], ['id' => 'uuid']);
        $this->assertTrue($validator->fails());
    }

    public function testJsonRule(): void
    {
        $validator = new Validator(['data' => '{"key":"value"}'], ['data' => 'json']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['data' => 'not json'], ['data' => 'json']);
        $this->assertTrue($validator->fails());
    }

    public function testIpRule(): void
    {
        $validator = new Validator(['ip' => '192.168.1.1'], ['ip' => 'ip']);
        $this->assertTrue($validator->passes());

        $validator = new Validator(['ip' => 'invalid'], ['ip' => 'ip']);
        $this->assertTrue($validator->fails());
    }

    public function testMultipleRules(): void
    {
        $validator = new Validator(
            ['email' => 'john@example.com'],
            ['email' => 'required|email|max:255']
        );
        $this->assertTrue($validator->passes());
    }

    public function testValidateThrowsException(): void
    {
        $this->expectException(ValidationException::class);

        $validator = new Validator([], ['name' => 'required']);
        $validator->validate();
    }

    public function testValidated(): void
    {
        $validator = new Validator(
            ['name' => 'John', 'extra' => 'ignored'],
            ['name' => 'required']
        );
        $validator->passes();

        $this->assertEquals(['name' => 'John'], $validator->validated());
    }

    public function testErrors(): void
    {
        $validator = new Validator([], ['name' => 'required']);
        $validator->fails();

        $errors = $validator->errors();
        $this->assertTrue($errors->has('name'));
    }

    public function testCustomMessages(): void
    {
        $validator = new Validator(
            [],
            ['name' => 'required'],
            ['name.required' => 'Please enter your name']
        );
        $validator->fails();

        $errors = $validator->errors();
        $this->assertEquals('Please enter your name', $errors->first('name'));
    }
}
