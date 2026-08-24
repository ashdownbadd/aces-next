<?php

declare(strict_types=1);

namespace App\Providers;

use App\Console\Commands\MigrateCommand;
use App\Console\Commands\SeedCommand;
use App\Console\Commands\SeedLedgerCommand;
use App\Console\Kernel;
use App\Console\Support\Migrator;
use App\Console\Support\SeederRunner;
use App\Foundation\Container;
use App\Foundation\Database;

final class ConsoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(
            Migrator::class,
            fn(Container $container) => new Migrator(
                $container->get(Database::class),
            ),
        );

        $this->container->singleton(
            SeederRunner::class,
            fn(Container $container) => new SeederRunner(
                $container->get(Database::class),
            ),
        );

        $this->container->singleton(
            Kernel::class,
            function (Container $container): Kernel {

                $kernel = new Kernel();

                $kernel->register(
                    new MigrateCommand(
                        $container->get(Migrator::class),
                    ),
                );

                $kernel->register(
                    new SeedCommand(
                        $container->get(SeederRunner::class),
                    ),
                );

                $kernel->register(
                    new SeedLedgerCommand(
                        $container->get(Database::class),
                    ),
                );

                return $kernel;
            },
        );
    }
}
