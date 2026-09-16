<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Features\Authentication\Services\AuthService;
use App\Http\Request;
use App\Http\Response;

final readonly class AdminMiddleware implements Middleware
{
    public function __construct(
        private AuthService $auth,
    ) {}

    public function handle(Request $request): ?Response
    {
        $user = $this->auth->user();

        if ($user === null || ! $user->hasRole('admin')) {
            return new Response(
                '403 Forbidden',
                403,
            );
        }

        return null;
    }
}
