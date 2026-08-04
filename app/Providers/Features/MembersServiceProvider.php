<?php

declare(strict_types=1);

namespace App\Providers\Features;

use App\Features\Members\Controllers\MembersController;
use App\Features\Members\Repositories\MemberRepository;
use App\Features\Members\Services\MemberService;
use App\Foundation\Container;
use App\Foundation\Database;
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
        | Service
        |--------------------------------------------------------------------------
        */

        $this->container->singleton(
            MemberService::class,
            fn(Container $container) => new MemberService(
                $container->get(MemberRepository::class),
            ),
        );

        /*
        |--------------------------------------------------------------------------
        | Controller
        |--------------------------------------------------------------------------
        */

        $this->container->singleton(
            MembersController::class,
            fn(Container $container) => new MembersController(
                $container->get(View::class),
                $container->get(MemberService::class),
            ),
        );
    }
}
