# Forjix Validation

Data validation library for the Forjix framework.

## Installation

```bash
composer require forjix/validation
```

## Basic Usage

```php
use Forjix\Validation\Validator;

$validator = new Validator($data, [
    'name' => 'required|string|min:2|max:255',
    'email' => 'required|email',
    'age' => 'required|integer|min:18',
    'password' => 'required|min:8|confirmed',
]);

if ($validator->fails()) {
    $errors = $validator->errors();
}

$validated = $validator->validated();
```

## Available Rules

| Rule | Description |
|------|-------------|
| `required` | Field must be present and not empty |
| `string` | Field must be a string |
| `integer` | Field must be an integer |
| `numeric` | Field must be numeric |
| `email` | Field must be a valid email |
| `url` | Field must be a valid URL |
| `min:n` | Minimum length/value |
| `max:n` | Maximum length/value |
| `between:min,max` | Value must be between min and max |
| `in:a,b,c` | Field must be one of the listed values |
| `not_in:a,b,c` | Field must not be one of the listed values |
| `confirmed` | Field must have a matching `{field}_confirmation` |
| `unique:table,column` | Field must be unique in database |
| `exists:table,column` | Field must exist in database |
| `regex:pattern` | Field must match the regex pattern |
| `date` | Field must be a valid date |
| `array` | Field must be an array |
| `boolean` | Field must be a boolean |

## Custom Rules

```php
use Forjix\Validation\Rules\Rule;

class Uppercase extends Rule
{
    public function passes(string $attribute, mixed $value): bool
    {
        return strtoupper($value) === $value;
    }

    public function message(): string
    {
        return 'The :attribute must be uppercase.';
    }
}

// Usage
$validator = new Validator($data, [
    'code' => ['required', new Uppercase()],
]);
```

## Password Validation

```php
use Forjix\Validation\Rules\Password;

$validator = new Validator($data, [
    'password' => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
]);
```

## License

GPL-3.0
