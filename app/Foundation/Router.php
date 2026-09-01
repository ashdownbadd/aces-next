<?php

declare(strict_types=1);

namespace App\Foundation;

use App\Http\Request;
use App\Http\Response;
use App\Foundation\CsrfToken;
use ReflectionMethod;
use RuntimeException;

final class Router
{
    /**
     * @var array<string, Route>
     */
    private array $routes = [];

    public function __construct(
        private readonly Container $container,
        private readonly CsrfToken $csrf,
    ) {}

    public function get(
        string $uri,
        callable|array $handler,
        array $middleware = [],
    ): void {
        $this->routes['GET:' . $uri] = new Route(
            method: 'GET',
            uri: $uri,
            handler: $handler,
            middleware: $middleware,
        );
    }

    public function post(
        string $uri,
        callable|array $handler,
        array $middleware = [],
    ): void {
        $this->routes['POST:' . $uri] = new Route(
            method: 'POST',
            uri: $uri,
            handler: $handler,
            middleware: $middleware,
        );
    }
    public function load(string $file): self
    {
        $router = $this;

        require $file;

        return $this;
    }

    public function dispatch(Request $request): Response
    {
        $routeMatch = $this->matchRoute(
            $request->method(),
            $request->uri(),
        );

        if ($routeMatch === null) {
            return new Response(
                '404 Not Found',
                404,
            );
        }

        $route = $routeMatch['route'];
        $parameters = $routeMatch['parameters'];

        if (
            $request->isPost()
            && ! $this->csrf->validate(
                is_string($request->input('_csrf'))
                    ? $request->input('_csrf')
                    : null,
            )
        ) {
            return new Response(
                '419 Page Expired — invalid or missing CSRF token.',
                419,
            );
        }

        $handler = $route->handler;

        foreach ($route->middleware as $middleware) {

            $instance = $this->container->get(
                $middleware,
            );

            $response = $instance->handle(
                $request,
            );

            if ($response !== null) {
                return $response;
            }
        }

        if (is_callable($handler)) {
            return $handler();
        }

        [$controller, $method] = $handler;

        $instance = $this->container->get(
            $controller,
        );

        if (! method_exists($instance, $method)) {
            throw new RuntimeException(
                sprintf(
                    'Method [%s::%s] does not exist.',
                    $controller,
                    $method,
                ),
            );
        }

        $reflection = new ReflectionMethod(
            $instance,
            $method,
        );

        $parameterCount =
            $reflection->getNumberOfParameters();

        if ($parameterCount === 0) {
            return $instance->$method();
        }

        /*
         * The first parameter is the Request object.
         * Any remaining parameters come from the route.
         */
        $arguments = [$request];

        foreach ($parameters as $parameter) {
            $arguments[] = $parameter;
        }

        return $instance->$method(...$arguments);
    }

    /**
     * Match an incoming request against registered routes.
     *
     * @return array{
     *     route: Route,
     *     parameters: array<int, mixed>
     * }|null
     */
    private function matchRoute(
        string $method,
        string $uri,
    ): ?array {
        $exactKey = $method . ':' . $uri;

        /*
         * Try an exact match first.
         *
         * This keeps all existing static routes fast
         * and preserves their current behavior.
         */
        if (isset($this->routes[$exactKey])) {
            return [
                'route' => $this->routes[$exactKey],
                'parameters' => [],
            ];
        }

        /*
         * Try routes containing dynamic parameters.
         */
        foreach ($this->routes as $route) {

            if ($route->method !== $method) {
                continue;
            }

            $routePattern = $this->compileRoute(
                $route->uri,
            );

            if (
                ! preg_match(
                    $routePattern['pattern'],
                    $uri,
                    $matches,
                )
            ) {
                continue;
            }

            $parameters = [];

            foreach ($routePattern['parameters'] as $name) {
                $parameters[] = $matches[$name];
            }

            return [
                'route' => $route,
                'parameters' => $parameters,
            ];
        }

        return null;
    }

    /**
     * Convert a route such as:
     *
     * /members/{id}
     *
     * into a regular expression.
     *
     * @return array{
     *     pattern: string,
     *     parameters: array<int, string>
     * }
     */
    private function compileRoute(
        string $uri,
    ): array {
        $parameters = [];

        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            function (array $matches) use (&$parameters): string {
                $name = $matches[1];

                $parameters[] = $name;

                return '(?P<' . $name . '>[^/]+)';
            },
            $uri,
        );

        if ($pattern === null) {
            throw new RuntimeException(
                sprintf(
                    'Unable to compile route [%s].',
                    $uri,
                ),
            );
        }

        return [
            'pattern' => '#^' . $pattern . '$#',
            'parameters' => $parameters,
        ];
    }
}
