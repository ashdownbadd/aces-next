<?php

declare(strict_types=1);

namespace App\Features\Authentication\Controllers;

use App\Features\Authentication\Services\AuthService;
use App\Foundation\View;
use App\Http\Request;
use App\Http\Response;

final readonly class LoginController
{
    public function __construct(
        private View $view,
        private AuthService $auth,
    ) {}

    public function show(): Response
    {
        return new Response(
            $this->view->render(
                'auth.login',
                [],
                'layouts.guest',
            )
        );
    }

    public function login(Request $request): Response
    {
        $username = trim((string) $request->input('username'));
        $password = (string) $request->input('password');

        $result = $this->auth->login($username, $password);

        if ($result === 'success') {
            return Response::redirect('/dashboard');
        }

        $error = $result === 'rate_limited'
            ? 'Too many login attempts. Please try again later.'
            : 'Invalid username or password.';

        return new Response(
            $this->view->render(
                'auth.login',
                [
                    'title' => 'Sign In',
                    'error' => $error,
                ],
                'layouts.guest',
            )
        );
    }

    public function logout(): Response
    {
        $this->auth->logout();

        return Response::redirect('/login');
    }
}
