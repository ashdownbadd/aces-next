<?php

declare(strict_types=1);

namespace App\Features\Dashboard\Controllers;

use App\Foundation\View;
use App\Http\Response;

final readonly class DashboardController
{
    public function __construct(
        private View $view,
    ) {}

    public function index(): Response
    {
        return new Response(
            $this->view->render(
                'dashboard.dashboard',
            ),
        );
    }
}
