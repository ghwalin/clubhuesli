<?php

declare(strict_types=1);

namespace Clubhuesli\Http;

/**
 * Maps a request to a controller action.
 *
 * Routes are declared with placeholders, e.g. `/persons/{id}`; the matched
 * values are handed to the action together with the request.
 */
final class Router
{
    /** @var array<int, array{method: string, regex: string, names: array<int, string>, action: callable}> Routes. */
    private array $routes = [];

    /**
     * Registers a route.
     *
     * @param string   $method  HTTP method.
     * @param string   $pattern Path pattern with `{name}` placeholders.
     * @param callable $action  Action receiving `(Request $request, array $parameters)`.
     */
    public function add(string $method, string $pattern, callable $action): void
    {
        $names = [];
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static function (array $matches) use (&$names): string {
                $names[] = $matches[1];

                return '([^/]+)';
            },
            '/' . trim($pattern, '/')
        ) ?? '';

        $this->routes[] = [
            'method' => strtoupper($method),
            'regex'  => '#^' . $regex . '$#',
            'names'  => $names,
            'action' => $action,
        ];
    }

    /**
     * Finds the matching route and runs its action.
     *
     * @param Request $request The request to dispatch.
     *
     * @return Response The response of the action.
     *
     * @throws HttpException When no route matches the path or the method.
     */
    public function dispatch(Request $request): Response
    {
        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $request->getPath(), $matches) !== 1) {
                continue;
            }
            $pathMatched = true;
            if ($route['method'] !== $request->getMethod()) {
                continue;
            }

            $parameters = [];
            foreach ($route['names'] as $index => $name) {
                $parameters[$name] = $matches[$index + 1];
            }

            return ($route['action'])($request, $parameters);
        }

        throw $pathMatched ? HttpException::methodNotAllowed() : HttpException::notFound('Unbekannter Endpunkt');
    }
}
