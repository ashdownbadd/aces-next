<?php

declare(strict_types=1);

namespace App\Providers\Features;

use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Members\Controllers\BeneficiaryController;
use App\Features\Members\Controllers\MembersController;
use App\Features\Members\Repositories\MemberRepository;
use App\Features\Members\Services\BeneficiaryService;
use App\Features\Members\Services\EditService;
use App\Features\Members\Services\MemberService;
use App\Features\Members\Services\RegistrationService;
use App\Features\Loans\Services\LoanService;
use App\Features\Members\Support\EditSession;
use App\Features\Members\Support\RegistrationSession;
use App\Foundation\Container;
use App\Foundation\Database;
use App\Foundation\Session;
use App\Foundation\View;
use App\Providers\ServiceProvider;

final class MembersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Repository
        |--------------------------------------------------------------------------
        */

        $this->container->singleton(
            MemberRepository::class,
            fn(Container $container) => new MemberRepository(
                $container->get(Database::class),
            ),
        );

        /*
        |--------------------------------------------------------------------------
        | Registration Session
        |--------------------------------------------------------------------------
        */

        $this->container->singleton(
            RegistrationSession::class,
            fn(Container $container) => new RegistrationSession(
                $container->get(Session::class),
            ),
        );

        /*
        |--------------------------------------------------------------------------
        | Edit Session
        |--------------------------------------------------------------------------
        */

        $this->container->singleton(
            EditSession::class,
            fn(Container $container) => new EditSession(
                $container->get(Session::class),
            ),
        );

        /*
        |--------------------------------------------------------------------------
        | Member Service
        |--------------------------------------------------------------------------
        */

        $this->container->singleton(
            MemberService::class,
            fn(Container $container) => new MemberService(
                $container->get(MemberRepository::class),
                $container->get(ActivityLogService::class),
                $container->get(Session::class),
            ),
        );

        /*
        |--------------------------------------------------------------------------
        | Registration Service
        |--------------------------------------------------------------------------
        */

        $this->container->singleton(
            RegistrationService::class,
            fn(Container $container) => new RegistrationService(
                $container->get(RegistrationSession::class),
                $container->get(MemberRepository::class),
                $container->get(ActivityLogService::class),
                $container->get(Session::class),
            ),
        );

        /*
        |--------------------------------------------------------------------------
        | Edit Service
        |--------------------------------------------------------------------------
        */

        $this->container->singleton(
            EditService::class,
            fn(Container $container) => new EditService(
                $container->get(EditSession::class),
                $container->get(MemberRepository::class),
                $container->get(ActivityLogService::class),
                $container->get(Session::class),
            ),
        );

        /*
        |--------------------------------------------------------------------------
        | Beneficiary Service
        |--------------------------------------------------------------------------
        */

        $this->container->singleton(
            BeneficiaryService::class,
            fn(Container $container) => new BeneficiaryService(
                $container->get(RegistrationSession::class),
                $container->get(EditSession::class),
                $container->get(ActivityLogService::class),
            ),
        );

        /*
        |--------------------------------------------------------------------------
        | Members Controller
        |--------------------------------------------------------------------------
        */

        $this->container->singleton(
            MembersController::class,
            fn(Container $container) => new MembersController(
                $container->get(View::class),
                $container->get(MemberService::class),
                $container->get(RegistrationService::class),
                $container->get(Session::class),
                $container->get(EditService::class),
                $container->get(LoanService::class),
            ),
        );

        /*
        |--------------------------------------------------------------------------
        | Beneficiary Controller
        |--------------------------------------------------------------------------
        */

        $this->container->singleton(
            BeneficiaryController::class,
            fn(Container $container) => new BeneficiaryController(
                $container->get(BeneficiaryService::class),
                $container->get(EditSession::class),
            ),
        );
    }
}
