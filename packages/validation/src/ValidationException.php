<?php

declare(strict_types=1);

namespace Forjix\Validation;

use Exception;

class ValidationException extends Exception
{
    protected Validator $validator;
    protected ValidationErrors $errors;
    protected int $status = 422;
    protected string $errorBag = 'default';
    protected string $redirectTo = '';

    public function __construct(Validator $validator, ?string $message = null)
    {
        parent::__construct($message ?? 'The given data was invalid.');

        $this->validator = $validator;
        $this->errors = $validator->errors();
    }

    public static function withMessages(array $messages): static
    {
        $validator = Validator::make([], []);

        foreach ($messages as $key => $value) {
            foreach ((array) $value as $message) {
                $validator->errors()->add($key, $message);
            }
        }

        return new static($validator);
    }

    public function errors(): ValidationErrors
    {
        return $this->errors;
    }

    public function validator(): Validator
    {
        return $this->validator;
    }

    public function status(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function errorBag(string $errorBag): static
    {
        $this->errorBag = $errorBag;

        return $this;
    }

    public function getErrorBag(): string
    {
        return $this->errorBag;
    }

    public function redirectTo(string $url): static
    {
        $this->redirectTo = $url;

        return $this;
    }

    public function getRedirectTo(): string
    {
        return $this->redirectTo;
    }
}
