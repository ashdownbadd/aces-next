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
            return $content;
        }

        return $this->renderFile(
            $layout,
            array_merge(
                $data,
                [
                    'content' => $content,
                ]
            )
        );
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
