<?php

declare(strict_types=1);

namespace App\Features\Members\Services;

use App\Features\Members\Support\RegistrationSession;
use App\Features\Members\Support\RegistrationWorkflow;

final class RegistrationService
{
    public function __construct(
        private readonly RegistrationSession $session,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public function saveStep(
        string $step,
        array $data,
    ): ?string {
        $this->session->putStep(
            $step,
            $data,
        );

        return RegistrationWorkflow::next(
            $step,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->session->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function step(
        string $step,
    ): array {
        return $this->session->getStep(
            $step,
        );
    }

    public function clear(): void
    {
        $this->session->clear();
    }
}
