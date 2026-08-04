<?php

declare(strict_types=1);

namespace App\Features\Authentication\Services;

use App\Domain\Authentication\User;
use App\Features\Authentication\Repositories\UserRepository;
use App\Foundation\Session;

final readonly class AuthService
{
    public function __construct(
        private UserRepository $users,
        private Session $session,
    ) {}

    public function login(string $username, string $password): bool
    {
        $user = $this->users->findByUsername($username);

        if ($user === null) {
            return false;
        }

        if (! $user->isActive()) {
            return false;
        }

        if (! password_verify($password, $user->password())) {
            return false;
        }

        $this->session->put('user_id', $user->id());

        $this->session->regenerate();

        return true;
    }

    public function logout(): void
    {
        $this->session->forget('user_id');
    }

    public function check(): bool
    {
        return $this->session->has('user_id');
    }

    public function user(): ?User
    {
        $id = $this->session->get('user_id');

        if ($id === null) {
            return null;
        }

        return $this->users->findById((int) $id);
    }
}
