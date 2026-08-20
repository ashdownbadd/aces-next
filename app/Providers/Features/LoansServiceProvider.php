<?php

declare(strict_types=1);

namespace App\Providers\Features;

use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Loans\Repositories\LoanRepository;
use App\Features\Loans\Repositories\LoanPaymentRepository;
use App\Features\Loans\Services\AmortizationService;
use App\Features\Loans\Services\LoanService;
use App\Features\Loans\Services\PaymentService;
use App\Features\Loans\Services\StatementOfAccountService;
use App\Features\Loans\Services\StatementOfAccountXlsx;
use App\Features\Members\Services\MemberService;
use App\Foundation\Container;
use App\Foundation\Session;
use App\Foundation\View;
use App\Features\Loans\Controllers\LoanController;
use App\Providers\ServiceProvider;

final class LoansServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(
            LoanRepository::class,
            fn (Container $container) => new LoanRepository(
                $container->get(\App\Foundation\Database::class),
            ),
        );

        $this->container->singleton(
            AmortizationService::class,
            fn (Container $container) => new AmortizationService(),
        );

        $this->container->singleton(
            LoanService::class,
            fn (Container $container) => new LoanService(
                $container->get(LoanRepository::class),
                $container->get(AmortizationService::class),
                $container->get(ActivityLogService::class),
                $container->get(Session::class),
            ),
        );

        $this->container->singleton(
            LoanPaymentRepository::class,
            fn (Container $container) => new LoanPaymentRepository(
                $container->get(\App\Foundation\Database::class),
            ),
        );

        $this->container->singleton(
            PaymentService::class,
            fn (Container $container) => new PaymentService(
                $container->get(LoanPaymentRepository::class),
                $container->get(LoanRepository::class),
                $container->get(AmortizationService::class),
                $container->get(ActivityLogService::class),
                $container->get(Session::class),
            ),
        );

        $this->container->singleton(
            StatementOfAccountService::class,
            fn (Container $container) => new StatementOfAccountService(
                $container->get(LoanRepository::class),
                $container->get(LoanPaymentRepository::class),
            ),
        );

        $this->container->singleton(
            StatementOfAccountXlsx::class,
            fn (Container $container) => new StatementOfAccountXlsx(),
        );

        $this->container->singleton(
            LoanController::class,
            fn (Container $container) => new LoanController(
                $container->get(View::class),
                $container->get(LoanService::class),
                $container->get(AmortizationService::class),
                $container->get(PaymentService::class),
                $container->get(LoanPaymentRepository::class),
                $container->get(StatementOfAccountService::class),
                $container->get(StatementOfAccountXlsx::class),
                $container->get(MemberService::class),
                $container->get(Session::class),
            ),
        );
    }
}
