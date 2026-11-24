<?php

declare(strict_types=1);

namespace Clubhuesli\Http;

/**
 * A JSON response of the API.
 */
final class Response
{
    /**
     * @param int        $status HTTP status code.
     * @param mixed|null $data   Payload; null sends an empty body.
     */
    private function __construct(
        private readonly int $status,
        private readonly mixed $data = null,
    ) {
    }

    /**
     * Creates a «200 OK» response.
     *
     * @param mixed $data Payload.
     *
     * @return self The response.
     */
    public static function ok(mixed $data): self
    {
        return new self(200, $data);
    }

    /**
     * Creates a «201 Created» response.
     *
     * @param mixed $data Payload.
     *
     * @return self The response.
     */
    public static function created(mixed $data): self
    {
        return new self(201, $data);
    }

    /**
     * Creates a «204 No Content» response.
     *
     * @return self The response.
     */
    public static function noContent(): self
    {
        return new self(204);
    }

    /**
     * Creates an error response.
     *
     * @param int                                               $status  HTTP status code.
     * @param string                                            $message Message shown to the user.
     * @param array<int, array{field: string, message: string}> $errors  Field errors, if any.
     *
     * @return self The response.
     */
    public static function error(int $status, string $message, array $errors = []): self
    {
        $data = ['error' => $message];
        if ($errors !== []) {
            $data['errors'] = $errors;
        }

        return new self($status, $data);
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
     * Returns the payload.
     *
     * @return mixed The payload.
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Sends status, headers and body to the client.
     */
    public function send(): void
    {
        http_response_code($this->getStatus());
        if ($this->getStatus() === 204 || $this->getData() === null) {
            return;
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->getData(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
