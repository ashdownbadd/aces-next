<?php

declare(strict_types=1);

namespace App\Features\Members\DTOs;

use App\Http\Request;

final class EducationData
{
    public function __construct(
        public readonly string $highestEducationalAttainment,
        public readonly string $schoolName = '',
        public readonly ?int $graduationYear = null,
    ) {}

    public static function fromRequest(
        Request $request,
    ): self {
        $year = $request->input(
            'graduation_year',
            null,
        );

        return new self(
            highestEducationalAttainment:
                (string) $request->input(
                    'highest_educational_attainment',
                    '',
                ),
            schoolName:
                (string) $request->input(
                    'school_name',
                    '',
                ),
            graduationYear:
                $year !== null && $year !== ''
                    ? (int) $year
                    : null,
        );
    }

    public static function fromArray(
        array $data,
    ): self {
        $year =
            $data['graduation_year'] ?? null;

        return new self(
            highestEducationalAttainment:
                (string) (
                    $data['highest_educational_attainment']
                    ?? ''
                ),
            schoolName:
                (string) (
                    $data['school_name'] ?? ''
                ),
            graduationYear:
                $year !== null && $year !== ''
                    ? (int) $year
                    : null,
        );
    }

    public function toArray(): array
    {
        return [
            'highest_educational_attainment' =>
                $this->highestEducationalAttainment,
            'school_name' =>
                $this->schoolName,
            'graduation_year' =>
                $this->graduationYear,
        ];
    }
}
