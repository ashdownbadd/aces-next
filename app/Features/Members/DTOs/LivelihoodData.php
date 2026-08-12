<?php

declare(strict_types=1);

namespace App\Features\Members\DTOs;

use App\Http\Request;

final class LivelihoodData
{
    public function __construct(
        public readonly string $livelihoodType,
        public readonly string $occupation,
        public readonly string $employer,
        public readonly string $monthlyIncome,
    ) {}

    public static function fromRequest(
        Request $request,
    ): self {
        return new self(
            livelihoodType: (string) $request->input(
                'employment_status',
                '',
            ),

            occupation: (string) $request->input(
                'occupation',
                '',
            ),

            employer: (string) $request->input(
                'employer',
                '',
            ),

            monthlyIncome: self::normalizeMoney(
                (string) $request->input(
                    'monthly_income',
                    '',
                ),
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
            livelihoodType: (string) (
                $data['employment_status'] ?? ''
            ),

            occupation: (string) (
                $data['occupation'] ?? ''
            ),

            employer: (string) (
                $data['employer'] ?? ''
            ),

            monthlyIncome: self::normalizeMoney(
                (string) (
                    $data['monthly_income'] ?? ''
                ),
            ),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'employment_status' => $this->livelihoodType,
            'occupation' => $this->occupation,
            'employer' => $this->employer,
            'monthly_income' => $this->monthlyIncome,
        ];
    }

    private static function normalizeMoney(
        string $value,
    ): string {
        return str_replace(
            ',',
            '',
            trim($value),
        );
    }
}
