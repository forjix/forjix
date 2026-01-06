<?php

declare(strict_types=1);

namespace Forjix\Validation;

use Forjix\Support\Arr;

class Validator
{
    protected array $data = [];
    protected array $rules = [];
    protected array $messages = [];
    protected array $customAttributes = [];
    protected array $errors = [];
    protected array $failedRules = [];

    protected static array $customRules = [];

    protected array $defaultMessages = [
        'required' => 'The :attribute field is required.',
        'string' => 'The :attribute must be a string.',
        'integer' => 'The :attribute must be an integer.',
        'numeric' => 'The :attribute must be a number.',
        'array' => 'The :attribute must be an array.',
        'boolean' => 'The :attribute must be true or false.',
        'email' => 'The :attribute must be a valid email address.',
        'url' => 'The :attribute must be a valid URL.',
        'min' => [
            'numeric' => 'The :attribute must be at least :min.',
            'string' => 'The :attribute must be at least :min characters.',
            'array' => 'The :attribute must have at least :min items.',
        ],
        'max' => [
            'numeric' => 'The :attribute may not be greater than :max.',
            'string' => 'The :attribute may not be greater than :max characters.',
            'array' => 'The :attribute may not have more than :max items.',
        ],
        'between' => 'The :attribute must be between :min and :max.',
        'size' => [
            'numeric' => 'The :attribute must be :size.',
            'string' => 'The :attribute must be :size characters.',
            'array' => 'The :attribute must contain :size items.',
        ],
        'in' => 'The selected :attribute is invalid.',
        'not_in' => 'The selected :attribute is invalid.',
        'confirmed' => 'The :attribute confirmation does not match.',
        'same' => 'The :attribute and :other must match.',
        'different' => 'The :attribute and :other must be different.',
        'regex' => 'The :attribute format is invalid.',
        'date' => 'The :attribute is not a valid date.',
        'date_format' => 'The :attribute does not match the format :format.',
        'before' => 'The :attribute must be a date before :date.',
        'after' => 'The :attribute must be a date after :date.',
        'alpha' => 'The :attribute may only contain letters.',
        'alpha_num' => 'The :attribute may only contain letters and numbers.',
        'alpha_dash' => 'The :attribute may only contain letters, numbers, dashes, and underscores.',
        'digits' => 'The :attribute must be :digits digits.',
        'digits_between' => 'The :attribute must be between :min and :max digits.',
        'unique' => 'The :attribute has already been taken.',
        'exists' => 'The selected :attribute is invalid.',
        'nullable' => '',
        'present' => 'The :attribute field must be present.',
        'filled' => 'The :attribute field must have a value.',
        'accepted' => 'The :attribute must be accepted.',
        'ip' => 'The :attribute must be a valid IP address.',
        'uuid' => 'The :attribute must be a valid UUID.',
        'json' => 'The :attribute must be a valid JSON string.',
    ];

    public function __construct(array $data, array $rules, array $messages = [], array $customAttributes = [])
    {
        $this->data = $data;
        $this->rules = $this->parseRules($rules);
        $this->messages = $messages;
        $this->customAttributes = $customAttributes;
    }

    public static function make(array $data, array $rules, array $messages = [], array $customAttributes = []): static
    {
        return new static($data, $rules, $messages, $customAttributes);
    }

    protected function parseRules(array $rules): array
    {
        $parsed = [];

        foreach ($rules as $attribute => $ruleSet) {
            if (is_string($ruleSet)) {
                $ruleSet = explode('|', $ruleSet);
            }

            $parsed[$attribute] = array_map(fn($rule) => $this->parseRule($rule), $ruleSet);
        }

        return $parsed;
    }

    protected function parseRule(string|array $rule): array
    {
        if (is_array($rule)) {
            return $rule;
        }

        if (str_contains($rule, ':')) {
            [$name, $parameters] = explode(':', $rule, 2);
            return ['name' => $name, 'parameters' => explode(',', $parameters)];
        }

        return ['name' => $rule, 'parameters' => []];
    }

    public function validate(): array
    {
        $this->errors = [];
        $this->failedRules = [];

        foreach ($this->rules as $attribute => $rules) {
            $this->validateAttribute($attribute, $rules);
        }

        if (!empty($this->errors)) {
            throw new ValidationException($this);
        }

        return $this->validated();
    }

    public function passes(): bool
    {
        $this->errors = [];
        $this->failedRules = [];

        foreach ($this->rules as $attribute => $rules) {
            $this->validateAttribute($attribute, $rules);
        }

        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    protected function validateAttribute(string $attribute, array $rules): void
    {
        $value = $this->getValue($attribute);

        // Check for nullable rule first
        if ($this->hasRule($rules, 'nullable') && ($value === null || $value === '')) {
            return;
        }

        foreach ($rules as $rule) {
            $ruleName = $rule['name'];
            $parameters = $rule['parameters'];

            if (!$this->validateRule($attribute, $value, $ruleName, $parameters)) {
                $this->addError($attribute, $ruleName, $parameters);
                $this->failedRules[$attribute][$ruleName] = $parameters;

                // Stop on first failure unless bail is false
                break;
            }
        }
    }

    protected function validateRule(string $attribute, mixed $value, string $rule, array $parameters): bool
    {
        $method = 'validate' . str_replace(' ', '', ucwords(str_replace('_', ' ', $rule)));

        if (isset(static::$customRules[$rule])) {
            return call_user_func(static::$customRules[$rule], $attribute, $value, $parameters, $this);
        }

        if (method_exists($this, $method)) {
            return $this->{$method}($attribute, $value, $parameters);
        }

        throw new \RuntimeException("Validation rule [{$rule}] does not exist.");
    }

    protected function hasRule(array $rules, string $ruleName): bool
    {
        foreach ($rules as $rule) {
            if ($rule['name'] === $ruleName) {
                return true;
            }
        }

        return false;
    }

    protected function getValue(string $attribute): mixed
    {
        return Arr::get($this->data, $attribute);
    }

    protected function addError(string $attribute, string $rule, array $parameters): void
    {
        $message = $this->getMessage($attribute, $rule, $parameters);
        $this->errors[$attribute][] = $message;
    }

    protected function getMessage(string $attribute, string $rule, array $parameters): string
    {
        // Check custom messages first
        if (isset($this->messages["{$attribute}.{$rule}"])) {
            $message = $this->messages["{$attribute}.{$rule}"];
        } elseif (isset($this->messages[$rule])) {
            $message = $this->messages[$rule];
        } elseif (isset($this->defaultMessages[$rule])) {
            $message = $this->defaultMessages[$rule];

            if (is_array($message)) {
                $value = $this->getValue($attribute);
                $type = is_numeric($value) ? 'numeric' : (is_array($value) ? 'array' : 'string');
                $message = $message[$type] ?? $message['string'] ?? '';
            }
        } else {
            $message = "The {$attribute} field is invalid.";
        }

        return $this->replaceMessagePlaceholders($message, $attribute, $parameters);
    }

    protected function replaceMessagePlaceholders(string $message, string $attribute, array $parameters): string
    {
        $displayAttribute = $this->customAttributes[$attribute] ?? str_replace('_', ' ', $attribute);

        $message = str_replace(':attribute', $displayAttribute, $message);

        foreach ($parameters as $index => $parameter) {
            $key = match ($index) {
                0 => ':min',
                1 => ':max',
                default => ":{$index}",
            };

            $message = str_replace($key, $parameter, $message);
            $message = str_replace(":other", $parameter, $message);
            $message = str_replace(":size", $parameter, $message);
            $message = str_replace(":digits", $parameter, $message);
            $message = str_replace(":format", $parameter, $message);
            $message = str_replace(":date", $parameter, $message);
        }

        return $message;
    }

    public function errors(): ValidationErrors
    {
        return new ValidationErrors($this->errors);
    }

    public function validated(): array
    {
        return Arr::only($this->data, array_keys($this->rules));
    }

    public function failed(): array
    {
        return $this->failedRules;
    }

    // Validation Rules

    protected function validateRequired(string $attribute, mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        if (is_array($value) && count($value) < 1) {
            return false;
        }

        return true;
    }

    protected function validateNullable(): bool
    {
        return true;
    }

    protected function validatePresent(string $attribute, mixed $value): bool
    {
        return Arr::has($this->data, $attribute);
    }

    protected function validateFilled(string $attribute, mixed $value): bool
    {
        if (Arr::has($this->data, $attribute)) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }

    protected function validateString(string $attribute, mixed $value): bool
    {
        return is_string($value);
    }

    protected function validateInteger(string $attribute, mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    protected function validateNumeric(string $attribute, mixed $value): bool
    {
        return is_numeric($value);
    }

    protected function validateArray(string $attribute, mixed $value): bool
    {
        return is_array($value);
    }

    protected function validateBoolean(string $attribute, mixed $value): bool
    {
        return in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true);
    }

    protected function validateEmail(string $attribute, mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validateUrl(string $attribute, mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    protected function validateMin(string $attribute, mixed $value, array $parameters): bool
    {
        $min = (int) $parameters[0];

        if (is_numeric($value)) {
            return $value >= $min;
        }

        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        }

        if (is_array($value)) {
            return count($value) >= $min;
        }

        return false;
    }

    protected function validateMax(string $attribute, mixed $value, array $parameters): bool
    {
        $max = (int) $parameters[0];

        if (is_numeric($value)) {
            return $value <= $max;
        }

        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        }

        if (is_array($value)) {
            return count($value) <= $max;
        }

        return false;
    }

    protected function validateBetween(string $attribute, mixed $value, array $parameters): bool
    {
        return $this->validateMin($attribute, $value, [$parameters[0]])
            && $this->validateMax($attribute, $value, [$parameters[1]]);
    }

    protected function validateSize(string $attribute, mixed $value, array $parameters): bool
    {
        $size = (int) $parameters[0];

        if (is_numeric($value)) {
            return $value == $size;
        }

        if (is_string($value)) {
            return mb_strlen($value) === $size;
        }

        if (is_array($value)) {
            return count($value) === $size;
        }

        return false;
    }

    protected function validateIn(string $attribute, mixed $value, array $parameters): bool
    {
        return in_array($value, $parameters, true);
    }

    protected function validateNotIn(string $attribute, mixed $value, array $parameters): bool
    {
        return !$this->validateIn($attribute, $value, $parameters);
    }

    protected function validateConfirmed(string $attribute, mixed $value): bool
    {
        return $this->validateSame($attribute, $value, [$attribute . '_confirmation']);
    }

    protected function validateSame(string $attribute, mixed $value, array $parameters): bool
    {
        $other = $this->getValue($parameters[0]);

        return $value === $other;
    }

    protected function validateDifferent(string $attribute, mixed $value, array $parameters): bool
    {
        return !$this->validateSame($attribute, $value, $parameters);
    }

    protected function validateRegex(string $attribute, mixed $value, array $parameters): bool
    {
        return preg_match($parameters[0], (string) $value) > 0;
    }

    protected function validateDate(string $attribute, mixed $value): bool
    {
        if ($value instanceof \DateTimeInterface) {
            return true;
        }

        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        return strtotime((string) $value) !== false;
    }

    protected function validateDateFormat(string $attribute, mixed $value, array $parameters): bool
    {
        $format = $parameters[0];
        $date = \DateTime::createFromFormat('!' . $format, $value);

        return $date && $date->format($format) === $value;
    }

    protected function validateBefore(string $attribute, mixed $value, array $parameters): bool
    {
        return strtotime($value) < strtotime($parameters[0]);
    }

    protected function validateAfter(string $attribute, mixed $value, array $parameters): bool
    {
        return strtotime($value) > strtotime($parameters[0]);
    }

    protected function validateAlpha(string $attribute, mixed $value): bool
    {
        return is_string($value) && preg_match('/^[\pL\pM]+$/u', $value);
    }

    protected function validateAlphaNum(string $attribute, mixed $value): bool
    {
        return is_string($value) && preg_match('/^[\pL\pM\pN]+$/u', $value);
    }

    protected function validateAlphaDash(string $attribute, mixed $value): bool
    {
        return is_string($value) && preg_match('/^[\pL\pM\pN_-]+$/u', $value);
    }

    protected function validateDigits(string $attribute, mixed $value, array $parameters): bool
    {
        return is_numeric($value) && strlen((string) $value) === (int) $parameters[0];
    }

    protected function validateDigitsBetween(string $attribute, mixed $value, array $parameters): bool
    {
        $length = strlen((string) $value);

        return is_numeric($value) && $length >= $parameters[0] && $length <= $parameters[1];
    }

    protected function validateAccepted(string $attribute, mixed $value): bool
    {
        return in_array($value, ['yes', 'on', '1', 1, true, 'true'], true);
    }

    protected function validateIp(string $attribute, mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    protected function validateUuid(string $attribute, mixed $value): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) > 0;
    }

    protected function validateJson(string $attribute, mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    // Custom Rules

    public static function extend(string $rule, callable $callback, ?string $message = null): void
    {
        static::$customRules[$rule] = $callback;
    }
}
