<?php

declare(strict_types=1);

namespace App\Features\Members\DTOs;

use App\Http\Request;

final class MembershipData
{
    public function __construct(
        public readonly string $membershipDate,
        public readonly string $membershipType,
    ) {}

    public static function fromRequest(
        Request $request,
    ): self {
        return new self(
            membershipDate: (string) $request->input(
                'membership_date',
                '',
            ),

            membershipType: (string) $request->input(
                'membership_type',
                'regular',
            ),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(
        array $data,
    ): self {
        return new self(
            membershipDate: (string) ($data['membership_date'] ?? ''),

            membershipType: (string) (
                $data['membership_type'] ?? 'regular'
            ),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'membership_date' => $this->membershipDate,
            'membership_type' => $this->membershipType,
        ];
    }
}
