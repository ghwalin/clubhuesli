<?php

declare(strict_types=1);

namespace Clubhuesli\Http;

use RuntimeException;

/**
 * An error that is reported to the client with a specific HTTP status code.
 */
class HttpException extends RuntimeException
{
    /**
     * @param int    $status  HTTP status code.
     * @param string $message Message shown to the user.
     */
    public function __construct(private readonly int $status, string $message)
    {
        parent::__construct($message);
    }

    /**
     * Returns the HTTP status code.
     *
     * @return int The status code.
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Creates a «400 Bad Request» error.
     *
     * @param string $message Message shown to the user.
     *
     * @return self The error.
     */
    public static function badRequest(string $message = 'Ungültige Anfrage'): self
    {
        return new self(400, $message);
    }

    /**
     * Creates a «401 Unauthorized» error.
     *
     * @param string $message Message shown to the user.
     *
     * @return self The error.
     */
    public static function unauthorized(string $message = 'Bitte anmelden'): self
    {
        return new self(401, $message);
    }

    /**
     * Creates a «403 Forbidden» error.
     *
     * @param string $message Message shown to the user.
     *
     * @return self The error.
     */
    public static function forbidden(string $message = 'Keine Berechtigung'): self
    {
        return new self(403, $message);
    }

    /**
     * Creates a «404 Not Found» error.
     *
     * @param string $message Message shown to the user.
     *
     * @return self The error.
     */
    public static function notFound(string $message = 'Nicht gefunden'): self
    {
        return new self(404, $message);
    }

    /**
     * Creates a «405 Method Not Allowed» error.
     *
     * @param string $message Message shown to the user.
     *
     * @return self The error.
     */
    public static function methodNotAllowed(string $message = 'Methode nicht erlaubt'): self
    {
        return new self(405, $message);
    }
}
