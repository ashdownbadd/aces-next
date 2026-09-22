<?php

declare(strict_types=1);

namespace App\Providers;

use App\Foundation\Config;
use App\Foundation\Container;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class ProviderLoader
{
    public function __construct(
        private readonly Container $container,
    ) {}

    public function register(): void
    {
        $directory = __DIR__;
        $providers = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            if ($file->getFilename() === 'ServiceProvider.php') {
                continue;
            }

            if ($file->getFilename() === 'ProviderLoader.php') {
                continue;
            }

            if (! str_ends_with($file->getFilename(), 'ServiceProvider.php')) {
                continue;
            }

            $relative = substr(
                $file->getPathname(),
                strlen(__DIR__) + 1
            );

            $class = 'App\\Providers\\'
                . str_replace(
                    ['/', '\\', '.php'],
                    ['\\', '\\', ''],
                    $relative
                );

            $providers[] = $class;
        }

        // Config must be initialized before any provider can resolve Session.
        // Otherwise Session may start before .env has loaded, which can cause
        // localhost HTTP sessions to receive a Secure cookie and lose the CSRF
        // token between GET /login and POST /login.
        usort(
            $providers,
            static fn(string $left, string $right): int =>
                ($left === FoundationServiceProvider::class ? -1 : 0)
                <=> ($right === FoundationServiceProvider::class ? -1 : 0),
        );

        foreach ($providers as $class) {
            (new $class($this->container))->register();
        }

        // Force environment/config loading after FoundationServiceProvider has
        // bound Config, but before the application resolves Session/CsrfToken.
        $this->container->get(Config::class);
    }
}
