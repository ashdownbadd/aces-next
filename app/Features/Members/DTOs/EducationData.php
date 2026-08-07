<?php

declare(strict_types=1);

namespace App\Features\Members\DTOs;

use App\Http\Request;

final class EducationData
{
    public function __construct(
        public readonly string $highestEducationalAttainment,
    ) {}

    public static function fromRequest(
        Request $request,
    ): self {
        return new self(
            highestEducationalAttainment: (string) $request->input(
                'highest_educational_attainment',
                '',
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
            highestEducationalAttainment: (string) (
                $data['highest_educational_attainment'] ?? ''
            ),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'highest_educational_attainment' => $this->highestEducationalAttainment,
        ];
    }
}
