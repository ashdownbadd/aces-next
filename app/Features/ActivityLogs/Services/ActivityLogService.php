<?php

declare(strict_types=1);

namespace App\Features\ActivityLogs\Services;

use App\Features\ActivityLogs\Repositories\ActivityLogRepository;

final readonly class ActivityLogService
{
    public function __construct(
        private ActivityLogRepository $repository,
    ) {}

    /**
     * Record a system activity.
     *
     * The service intentionally contains no module-specific rules.
     * Members, Loans, Ledger, and other features can reuse it.
     */
    public function record(
        ?int $userId,
        string $action,
        ?string $description = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?string $ipAddress = null,
    ): void {
        $action = trim($action);

        if ($action === '') {
            throw new \InvalidArgumentException(
                'Activity log action cannot be empty.'
            );
        }

        if ($subjectType !== null) {
            $subjectType = trim($subjectType);

            if ($subjectType === '') {
                $subjectType = null;
            }
        }

        if ($ipAddress !== null) {
            $ipAddress = trim($ipAddress);

            if ($ipAddress === '') {
                $ipAddress = null;
            }
        }

        $this->repository->create(
            $userId,
            $action,
            $description,
            $subjectType,
            $subjectId,
            $ipAddress,
        );
    }
}
