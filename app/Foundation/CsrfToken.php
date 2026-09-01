<?php

declare(strict_types=1);

namespace App\Foundation;

use RuntimeException;

final class CsrfToken
{
    private const SESSION_KEY = '_csrf_token';

    public function __construct(
        private readonly Session $session,
    ) {}

    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);

        if (
            ! is_string($token)
            || $token === ''
        ) {
            $token = bin2hex(random_bytes(32));

            $this->session->put(
                self::SESSION_KEY,
                $token,
            );
        }

        return $token;
    }

    public function validate(
        ?string $token,
    ): bool {
        if (
            $token === null
            || $token === ''
        ) {
            return false;
        }

        $stored = $this->session->get(self::SESSION_KEY);

        if (
            ! is_string($stored)
            || $stored === ''
        ) {
            return false;
        }

        return hash_equals(
            $stored,
            $token,
        );
    }
}
