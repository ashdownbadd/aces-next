<?php

declare(strict_types=1);

namespace App\Foundation;

use App\Http\Request;
use App\Http\Response;
use App\Foundation\Route;
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
        $key = $request->method() . ':' . $request->uri();

        if (! isset($this->routes[$key])) {
            return new Response('404 Not Found', 404);
        }

        $route = $this->routes[$key];

        $handler = $route->handler;

        foreach ($route->middleware as $middleware) {

            $instance = $this->container->get($middleware);

            $response = $instance->handle($request);

            if ($response !== null) {
                return $response;
            }
        }

        if (is_callable($handler)) {
            return $handler();
        }

        [$controller, $method] = $handler;

        $instance = $this->container->get($controller);

        if (! method_exists($instance, $method)) {
            throw new RuntimeException(
                sprintf(
                    'Method [%s::%s] does not exist.',
                    $controller,
                    $method
                )
            );
        }

        $reflection = new ReflectionMethod($instance, $method);

        if ($reflection->getNumberOfParameters() === 0) {
            return $instance->$method();
        }

        return $instance->$method($request);
    }
}
