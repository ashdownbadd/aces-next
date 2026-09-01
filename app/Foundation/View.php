<?php

declare(strict_types=1);

namespace App\Foundation;

use RuntimeException;

final class View
{
    /**
     * @var array<string, mixed>
     */
    private array $sharedData = [];

    public function __construct(
        private readonly string $viewsPath,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public function render(
        string $view,
        array $data = [],
        ?string $layout = null,
    ): string {
        $this->sharedData = $data;

        $content = $this->renderFile($view, $data);

        if ($layout === null) {
            return $this->injectCsrfFields($content);
        }

        $layoutContent = $this->renderFile(
            $layout,
            array_merge(
                $data,
                [
                    'content' => $content,
                ]
            )
        );

        return $this->injectCsrfFields($layoutContent);
    }

    /**
     * Render a reusable partial.
     */
    public function partial(string $view): string
    {
        return $this->renderFile(
            $view,
            $this->sharedData,
        );
    }

    /**
     * Add a CSRF token to every server-rendered POST form.
     *
     * This keeps the protection centralized so new POST forms cannot
     * accidentally ship without a token.
     */
    private function injectCsrfFields(string $html): string
    {
        $token = $_SESSION['_csrf_token'] ?? null;

        if (
            ! is_string($token)
            || $token === ''
        ) {
            $token = bin2hex(random_bytes(32));

            $_SESSION['_csrf_token'] = $token;
        }

        return preg_replace_callback(
            '/<form\b([^>]*)>/i',
            static function (array $matches) use ($token): string {
                $attributes = $matches[1];

                if (
                    ! preg_match(
                        '/\bmethod\s*=\s*["\']post["\']/i',
                        $attributes,
                    )
                ) {
                    return $matches[0];
                }

                if (
                    preg_match(
                        '/name\s*=\s*["\']_csrf["\']/i',
                        $attributes,
                    )
                ) {
                    return $matches[0];
                }

                return $matches[0]
                    . "\n<input type=\"hidden\" name=\"_csrf\" value=\""
                    . htmlspecialchars(
                        $token,
                        ENT_QUOTES,
                        'UTF-8',
                    )
                    . "\">";
            },
            $html,
        ) ?? $html;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderFile(
        string $view,
        array $data,
    ): string {
        $file = sprintf(
            '%s/%s.php',
            rtrim($this->viewsPath, '/\\'),
            str_replace('.', '/', $view),
        );

        if (! file_exists($file)) {
            throw new RuntimeException(
                sprintf(
                    'View [%s] does not exist.',
                    $view,
                )
            );
        }

        $viewRenderer = $this;

        extract($data, EXTR_SKIP);

        $view = $viewRenderer;

        ob_start();

        require $file;

        return (string) ob_get_clean();
    }
}
