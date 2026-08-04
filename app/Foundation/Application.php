<?php

declare(strict_types=1);

namespace App\Foundation;

use App\Http\Request;

final class Application
{
    public function __construct(
        private readonly Container $container,
    ) {}

    public function run(): void
    {
        $request = Request::capture();

        $router = $this->container->get(Router::class);

        $response = $router->dispatch($request);

        $response->send();
    }

    public function container(): Container
    {
        return $this->container;
    }
}
