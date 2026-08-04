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

        foreach ($this->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }

        echo $this->content;
    }
}
