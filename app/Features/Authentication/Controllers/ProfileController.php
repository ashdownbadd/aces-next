<?php

declare(strict_types=1);

namespace App\Features\Authentication\Controllers;

use App\Features\Authentication\Services\AuthService;
use App\Foundation\View;
use App\Http\Request;
use App\Http\Response;
use InvalidArgumentException;

final readonly class ProfileController
{
    public function __construct(
        private View $view,
        private AuthService $auth,
    ) {}

    public function show(): Response
    {
        $user = $this->auth->user();

        if ($user === null) {
            return Response::redirect('/login');
        }

        $success = $_SESSION['settings_success'] ?? null;
        $error = $_SESSION['settings_error'] ?? null;
        unset($_SESSION['settings_success'], $_SESSION['settings_error']);

        return new Response(
            $this->view->render(
                'auth.settings',
                [
                    'title' => 'Settings',
                    'user' => $user,
                    'success' => $success,
                    'error' => $error,
                ],
                'layouts.app',
            ),
        );
    }

    public function update(Request $request): Response
    {
        $user = $this->auth->user();

        if ($user === null || $user->id() === null) {
            return Response::redirect('/login');
        }

        $username = trim((string) $request->input('username', ''));
        $currentPassword = (string) $request->input('current_password', '');
        $newPassword = (string) $request->input('new_password', '');
        $confirmPassword = (string) $request->input('new_password_confirmation', '');

        if (strlen($username) < 3 || strlen($username) > 50) {
            return $this->error('Username must be between 3 and 50 characters.');
        }

        if (!preg_match('/^[A-Za-z0-9._-]+$/', $username)) {
            return $this->error('Username may contain only letters, numbers, dots, underscores, and hyphens.');
        }

        if ($newPassword !== '' && $confirmPassword !== $newPassword) {
            return $this->error('New password and confirmation do not match.');
        }

        try {
            $this->auth->updateCredentials(
                $user->id(),
                $username,
                $currentPassword,
                $newPassword,
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage());
        }

        $_SESSION['success_message'] = 'Settings updated successfully.';

        return Response::redirect('/dashboard');
    }

    private function error(string $message): Response
    {
        $_SESSION['settings_error'] = $message;

        return Response::redirect('/settings');
    }
}
