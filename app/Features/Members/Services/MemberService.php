<?php

declare(strict_types=1);

namespace App\Features\Members\Services;

use App\Features\Members\DTOs\MembershipData;
use App\Features\Members\DTOs\PersonalData;
use App\Features\Members\Repositories\MemberRepository;

final class MemberService
{
    public function __construct(
        private readonly MemberRepository $repository,
    ) {}

    public function all(): array
    {
        return $this->repository->all();
    }

    public function count(): int
    {
        return $this->repository->count();
    }

    public function create(
        MembershipData $membership,
        PersonalData $personal,
    ): int {
        $memberNumber = $this->generateMemberNumber();

        return $this->repository->create(
            $membership,
            $personal,
            $memberNumber,
        );
    }

    private function generateMemberNumber(): string
    {
        $lastMemberNumber = $this->repository->lastMemberNumber();

        if ($lastMemberNumber === null) {
            return '000001';
        }

        return str_pad(
            (string) (((int) $lastMemberNumber) + 1),
            6,
            '0',
            STR_PAD_LEFT,
        );
    }
}
