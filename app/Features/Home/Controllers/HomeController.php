<?php

declare(strict_types=1);

namespace App\Features\Home\Controllers;

use App\Foundation\Config;
use App\Foundation\Database;
use App\Foundation\Session;
use App\Foundation\View;
use App\Http\Response;

final class HomeController
{
    public function __construct(
        private readonly Config $config,
        private readonly View $view,
        private readonly Database $database,
        private readonly Session $session,
    ) {}

    public function index(): Response
    {
        // Temporary session test
        $this->session->put('test', 'ACES Next');

        // Temporary database test
        $this->database->connection();

        return new Response(
            $this->view->render(
                view: 'home.index',
                data: [
                    'title'        => 'Home',
                    'appName'      => $this->config->get('app.name'),
                    'sessionValue' => $this->session->get('test'),
                ],
                layout: 'layouts.app',
            )
        );
    }
}
