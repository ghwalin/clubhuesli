<?php

declare(strict_types=1);

namespace Clubhuesli\Validation;

use RuntimeException;

/**
 * Thrown when a value fails validation in a setter or when a model is not
 * consistent as a whole.
 */
class ValidationException extends RuntimeException
{
    /** @var array<int, array{field: string, message: string}> Collected errors. */
    private array $errors;

    /**
     * @param string $field   Name of the offending field (e.g. `birthDate`).
     * @param string $message Message shown to the user.
     */
    public function __construct(string $field, string $message)
    {
        parent::__construct($message);
        $this->errors = [['field' => $field, 'message' => $message]];
    }

    /**
     * Creates an exception carrying several errors at once.
     *
     * @param array<int, array{field: string, message: string}> $errors Collected errors.
     *
     * @return self The exception, never thrown by this method.
     */
    public static function fromErrors(array $errors): self
    {
        $first     = $errors[0] ?? ['field' => '', 'message' => 'Ungültige Eingabe'];
        $exception = new self($first['field'], $first['message']);
        $exception->errors = $errors;

        return $exception;
    }

    /**
     * Returns every error of this exception.
     *
     * @return array<int, array{field: string, message: string}> The errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
