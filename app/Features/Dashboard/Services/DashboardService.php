<?php

declare(strict_types=1);

namespace App\Features\Dashboard\Services;

use App\Features\Authentication\Services\AuthService;
use App\Features\Dashboard\Repositories\DashboardRepository;

final readonly class DashboardService
{
    public function __construct(
        private AuthService $auth,
        private DashboardRepository $repository,
    ) {}

    public function data(): array
    {
        return [
            'user' => $this->auth->user(),
            'stats' => $this->repository->stats(),
        ];
    }
}
