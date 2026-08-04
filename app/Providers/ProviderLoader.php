<?php

declare(strict_types=1);

namespace App\Providers;

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

            (new $class($this->container))->register();
        }
    }
}
