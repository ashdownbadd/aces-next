<?php

declare(strict_types=1);

namespace App\Foundation;

use App\Http\Request;
use App\Http\Response;
use Throwable;

final class Application
{
    public function __construct(
        private readonly Container $container,
    ) {}

    public function run(): void
    {
        $request = Request::capture();

        try {
            $router = $this->container->get(Router::class);
            $response = $router->dispatch($request);
            $response->send();
        } catch (Throwable $exception) {
            if ($this->debugEnabled()) {
                throw $exception;
            }

            error_log(sprintf(
                '[ACES] Unhandled exception for %s %s: %s in %s:%d\n%s',
                $request->method(),
                $request->uri(),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $exception->getTraceAsString(),
            ));

            (new Response(
                content: $this->serverErrorPage(),
                status: 500,
                headers: ['Content-Type' => 'text/html; charset=UTF-8'],
            ))->send();
        }
    }

    private function debugEnabled(): bool
    {
        $config = $this->container->get(Config::class);

        return (bool) $config->get('app.debug', false);
    }

    private function serverErrorPage(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error | ACES</title>
</head>
<body>
    <main>
        <h1>Something went wrong.</h1>
        <p>We couldn't complete your request. Please try again. If the problem continues, contact an administrator.</p>
    </main>
</body>
</html>
HTML;
    }

    public function container(): Container
    {
        return $this->container;
    }
}
