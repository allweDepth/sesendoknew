<?php

/** Validation failures that can be shown beside a specific form control. */
final class FieldValidationException extends InvalidArgumentException
{
    public readonly array $errors;

    public function __construct(string $field, string $message)
    {
        parent::__construct($message);
        $this->errors = [$field => [$message]];
    }
}
