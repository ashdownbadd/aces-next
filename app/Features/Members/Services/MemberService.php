<?php

declare(strict_types=1);

namespace App\Features\Members\Services;

use App\Features\Members\DTOs\MemberRegistrationData;
use App\Features\Members\Repositories\MemberRepository;

final class MemberService
{
    public function __construct(
        private readonly MemberRepository $repository,
    ) {}

    /**
     * Retrieve a paginated list of members.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(
        string $search = '',
        int $limit = 25,
        int $offset = 0,
    ): array {
        return $this->repository->all(
            $search,
            $limit,
            $offset,
        );
    }

    /**
     * Return the number of members matching a search query.
     */
    public function count(
        string $search = '',
    ): int {
        return $this->repository->count(
            $search,
        );
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
        $lastNumber = $this->repository->lastMemberNumber();

        if ($lastNumber === null) {
            return '0001';
        }

        $nextNumber = (int) $lastNumber + 1;

        if ($nextNumber > 9999) {
            throw new \RuntimeException(
                'Member number limit reached. Maximum member number is 9999.'
            );
        }

        return str_pad(
            (string) $nextNumber,
            4,
            '0',
            STR_PAD_LEFT,
        );
    }
}
