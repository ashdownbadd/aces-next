<?php

declare(strict_types=1);

namespace App\Features\Dashboard\Controllers;

use App\Features\Dashboard\Services\DashboardService;
use App\Foundation\View;
use App\Http\Response;

final readonly class DashboardController
{
    public function __construct(
        private View $view,
        private DashboardService $dashboardService,
    ) {}

    public function index(): Response
    {
        return new Response(
            $this->view->render(
                'dashboard.dashboard',
                $this->dashboardService->snapshot(),
                'layouts.app',
            ),
        );
    }
}
