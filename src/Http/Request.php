<?php

declare(strict_types=1);

namespace Clubhuesli\Http;

/**
 * The incoming HTTP request: method, path, query parameters and JSON body.
 */
final class Request
{
    /**
     * @param string               $method Upper-case HTTP method.
     * @param string               $path   Path below the API root, e.g. `/persons/5`.
     * @param array<string, mixed> $query  Query parameters.
     * @param array<string, mixed> $body   Decoded JSON body.
     */
    private function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $body,
    ) {
    }

    /**
     * Builds the request from the PHP superglobals.
     *
     * @return self The current request.
     *
     * @throws HttpException When the body is not valid JSON.
     */
    public static function fromGlobals(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $path   = self::resolvePath();
        $body   = [];

        $raw = file_get_contents('php://input');
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                throw HttpException::badRequest('Ungültiger JSON-Inhalt');
            }
            $body = $decoded;
        }

        return new self($method, $path, $_GET, $body);
    }

    /**
     * Builds a request by hand (used by tests).
     *
     * @param string               $method HTTP method.
     * @param string               $path   Path below the API root.
     * @param array<string, mixed> $body   Request body.
     * @param array<string, mixed> $query  Query parameters.
     *
     * @return self The request.
     */
    public static function create(string $method, string $path, array $body = [], array $query = []): self
    {
        return new self(strtoupper($method), '/' . trim($path, '/'), $query, $body);
    }

    /**
     * Returns the HTTP method.
     *
     * @return string The upper-case method.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Returns the path below the API root.
     *
     * @return string The path, always starting with a slash.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Returns the path split into segments.
     *
     * @return array<int, string> The non-empty segments.
     */
    public function getSegments(): array
    {
        return array_values(array_filter(explode('/', $this->getPath()), static fn (string $s): bool => $s !== ''));
    }

    /**
     * Returns a query parameter.
     *
     * @param string      $key     Name of the parameter.
     * @param string|null $default Value returned when the parameter is absent.
     *
     * @return string|null The value.
     */
    public function getQueryParam(string $key, ?string $default = null): ?string
    {
        $value = $this->query[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : $default;
    }

    /**
     * Returns the decoded JSON body.
     *
     * @return array<string, mixed> The body.
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * Determines the path below the API root from the server variables.
     *
     * Works both with a rewrite to `/api/index.php` and with the built-in PHP
     * development server.
     *
     * @return string The path, always starting with a slash.
     */
    private static function resolvePath(): string
    {
        $pathInfo = (string) ($_SERVER['PATH_INFO'] ?? '');
        if ($pathInfo === '') {
            $uri  = (string) ($_SERVER['REQUEST_URI'] ?? '/');
            $path = parse_url($uri, PHP_URL_PATH);
            $path = is_string($path) ? $path : '/';
            // Cut everything up to and including the API root, so the app also
            // works in a subdirectory such as /clubhuesli/public/api/persons.
            $pathInfo = preg_match('#^.*?/api(/.*)?$#', $path, $matches) === 1
                ? ($matches[1] ?? '/')
                : $path;
            $pathInfo = preg_replace('#^/index\.php#', '', $pathInfo) ?? $pathInfo;
        }

        return '/' . trim($pathInfo, '/');
    }
}
