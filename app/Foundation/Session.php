<?php

declare(strict_types=1);

namespace App\Foundation;

final class Session
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $https = ($_SERVER['HTTPS'] ?? '') !== ''
            && ($_SERVER['HTTPS'] ?? '') !== 'off';

        $forwardedProto = strtolower(
            trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))
        );

        $secure = $https
            || $forwardedProto === 'https'
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');

        // Keep session state in cookies only and reject untrusted session IDs.
        // The Secure flag follows the actual request transport so local HTTP
        // development does not silently discard the session cookie.
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_cookies', '1');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
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

    public function save(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
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
