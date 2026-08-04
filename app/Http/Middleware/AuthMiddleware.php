<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Features\Authentication\Services\AuthService;
use App\Http\Request;
use App\Http\Response;

final readonly class AuthMiddleware implements Middleware
{
    public function __construct(
        private AuthService $auth,
    ) {}

    public function handle(Request $request): ?Response
    {
        if (! $this->auth->check()) {
            return Response::redirect('/login');
        }

        return null;
    }
}
