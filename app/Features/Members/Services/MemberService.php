<?php

declare(strict_types=1);

namespace App\Features\Members\Services;

use App\Features\Members\DTOs\MemberRegistrationData;
use App\Features\ActivityLogs\Services\ActivityLogService;
use App\Features\Members\Repositories\MemberRepository;
use App\Foundation\Session;

final class MemberService
{
    public function __construct(
        private readonly MemberRepository $repository,
        private readonly ActivityLogService $activityLog,
        private readonly Session $session,
    ) {}

    /**
     * Retrieve a paginated list of members.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(
        string $search = '',
        string $status = '',
        int $limit = 25,
        int $offset = 0,
    ): array {
        $status = $this->normalizeStatus($status);

        return $this->repository->all(
            $search,
            $status,
            $limit,
            $offset,
        );
    }

    /**
     * Return the number of members matching a search query.
     */
    public function count(
        string $search = '',
        string $status = '',
    ): int {
        $status = $this->normalizeStatus($status);

        return $this->repository->count(
            $search,
            $status,
        );
    }

    /**
     * Normalize the optional member status filter.
     *
     * An empty status means all members.
     */
    private function normalizeStatus(string $status): string
    {
        $status = trim($status);

        if ($status === '') {
            return '';
        }

        $allowedStatuses = [
            'Pending',
            'Active',
            'Inactive',
        ];

        if (! in_array($status, $allowedStatuses, true)) {
            return '';
        }

        return $status;
    }

    /**
     * Retrieve one complete member profile.
     */
    public function find(int $id): ?array
    {
        return $this->repository->find($id);
    }

    /**
     * Change a member's status using the allowed lifecycle transitions.
     *
     * Allowed transitions:
     * - Pending -> Active
     * - Pending -> Inactive
     * - Active -> Inactive
     * - Inactive -> Active
     */
    public function changeStatus(
        int $memberId,
        string $newStatus,
    ): void {
        $member = $this->repository->find($memberId);

        if ($member === null) {
            throw new \RuntimeException(
                'Member not found.',
            );
        }

        $currentStatus = (string) (
            $member['status'] ?? ''
        );

        $allowedTransitions = [
            'Pending' => [
                'Active',
                'Inactive',
            ],
            'Active' => [
                'Inactive',
            ],
            'Inactive' => [
                'Active',
            ],
        ];

        if (
            ! isset(
                $allowedTransitions[$currentStatus]
            )
        ) {
            throw new \RuntimeException(
                'The member has an invalid current status.',
            );
        }

        if (
            ! in_array(
                $newStatus,
                $allowedTransitions[$currentStatus],
                true,
            )
        ) {
            throw new \InvalidArgumentException(
                "Cannot change member status from {$currentStatus} to {$newStatus}.",
            );
        }

        $this->repository->updateStatus(
            $memberId,
            $newStatus,
        );

        $userId = $this->session->get('user_id');

        $this->activityLog->record(
            userId: $userId !== null
                ? (int) $userId
                : null,
            action: 'MEMBER_STATUS_CHANGED',
            description: sprintf(
                'Member #%s status changed from %s to %s.',
                (string) (
                    $member['member_number']
                    ?? $memberId
                ),
                $currentStatus,
                $newStatus,
            ),
            subjectType: 'Member',
            subjectId: $memberId,
            ipAddress: $_SERVER['REMOTE_ADDR'] ?? null,
        );
    }

    /**
     * Update an existing member.
     */
    public function update(
        int $memberId,
        MemberRegistrationData $registration,
    ): void {
        $this->repository->update(
            $memberId,
            $registration,
        );
    }

    /**
     * Generate the next 4-digit member number.
     */
    public function nextMemberNumber(): string
    {
        return $this->repository->nextMemberNumberPreview();
    }
}
