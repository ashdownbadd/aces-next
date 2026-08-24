<?php

declare(strict_types=1);

namespace App\Providers\Features;

use App\Features\Ledger\Controllers\LedgerController;
use App\Features\Ledger\Repositories\JournalVoucherRepository;
use App\Features\Ledger\Services\LedgerService;
use App\Foundation\Container;
use App\Foundation\Database;
use App\Foundation\Session;
use App\Foundation\View;
use App\Providers\ServiceProvider;

final class LedgerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(
            JournalVoucherRepository::class,
            fn(Container $container) => new JournalVoucherRepository(
                $container->get(Database::class),
            ),
        );

        $this->container->singleton(
            LedgerService::class,
            fn(Container $container) => new LedgerService(
                $container->get(JournalVoucherRepository::class),
            ),
        );

        $this->container->singleton(
            LedgerController::class,
            fn(Container $container) => new LedgerController(
                $container->get(View::class),
                $container->get(JournalVoucherRepository::class),
                $container->get(LedgerService::class),
                $container->get(Session::class),
            ),
        );
    }
}
