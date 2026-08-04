<?php

declare(strict_types=1);

namespace App\Foundation;

final class Session
{
    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function flush(): void
    {
        $_SESSION = [];
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}
