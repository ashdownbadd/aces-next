<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
    public function __construct(
        private readonly string $content = '',
        private readonly int $status = 200,
        private readonly array $headers = [],
    ) {}

    public static function redirect(
        string $location,
        int $status = 302,
    ): self {
        return new self(
            status: $status,
            headers: [
                'Location' => $location,
            ],
        );
    }

    public function send(): void
    {
        http_response_code($this->status);

        $defaultHeaders = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ];

        if (
            (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        ) {
            $defaultHeaders['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach (array_merge($defaultHeaders, $this->headers) as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }

        echo $this->content;
    }
}
