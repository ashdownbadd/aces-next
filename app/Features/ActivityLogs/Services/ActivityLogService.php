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
     * @return array<int, array<string, mixed>>
     */
    public function all(
        string $search = '',
        string $action = '',
        int $userId = 0,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $limit = 25,
        int $offset = 0,
    ): array {
        return $this->repository->all(
            $search,
            $action,
            $userId,
            $dateFrom,
            $dateTo,
            $limit,
            $offset,
        );
    }

    public function count(
        string $search = '',
        string $action = '',
        int $userId = 0,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): int {
        return $this->repository->count(
            $search,
            $action,
            $userId,
            $dateFrom,
            $dateTo,
        );
    }

    /** @return array<int, string> */
    public function actions(): array
    {
        return $this->repository->actions();
    }

    /** @return array<int, array<string, mixed>> */
    public function users(): array
    {
        return $this->repository->users();
    }

    /**
     * Retrieve one activity log by ID.
     *
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        return $this->repository->find($id);
    }

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
