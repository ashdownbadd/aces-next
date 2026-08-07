<?php

declare(strict_types=1);

namespace App\Providers\Features;

use App\Features\Members\Controllers\BeneficiaryController;
use App\Features\Members\Controllers\MembersController;
use App\Features\Members\Repositories\MemberRepository;
use App\Features\Members\Services\BeneficiaryService;
use App\Features\Members\Services\MemberService;
use App\Features\Members\Services\RegistrationService;
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
        | Support
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
        | Services
        |--------------------------------------------------------------------------
        */

        $this->container->singleton(
            MemberService::class,
            fn(Container $container) => new MemberService(
                $container->get(MemberRepository::class),
            ),
        );

        $this->container->singleton(
            RegistrationService::class,
            fn(Container $container) => new RegistrationService(
                $container->get(RegistrationSession::class),
            ),
        );

        $this->container->singleton(
            BeneficiaryService::class,
            fn(Container $container) => new BeneficiaryService(
                $container->get(RegistrationSession::class),
            ),
        );

        /*
        |--------------------------------------------------------------------------
        | Controllers
        |--------------------------------------------------------------------------
        */

        $this->container->singleton(
            MembersController::class,
            fn(Container $container) => new MembersController(
                $container->get(View::class),
                $container->get(MemberService::class),
                $container->get(RegistrationService::class),
            ),
        );

        $this->container->singleton(
            BeneficiaryController::class,
            fn(Container $container) => new BeneficiaryController(
                $container->get(BeneficiaryService::class),
            ),
        );
    }
}
