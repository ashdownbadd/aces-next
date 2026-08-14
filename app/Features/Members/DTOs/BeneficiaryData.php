<?php

declare(strict_types=1);

namespace App\Features\Members\DTOs;

use App\Http\Request;

final class BeneficiaryData
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $middleName,
        public readonly string $lastName,
        public readonly string $suffix,
        public readonly string $relationship,
        public readonly string $birthDate,
        public readonly string $remarks,
    ) {}

    public static function fromRequest(
        Request $request,
    ): self {
        return new self(
            firstName: (string) $request->input(
                'first_name',
                '',
            ),

            middleName: (string) $request->input(
                'middle_name',
                '',
            ),

            lastName: (string) $request->input(
                'last_name',
                '',
            ),

            suffix: (string) $request->input(
                'suffix',
                '',
            ),

            relationship: (string) $request->input(
                'relationship',
                '',
            ),

            birthDate: (string) $request->input(
                'birth_date',
                '',
            ),

            remarks: (string) $request->input(
                'remarks',
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
            firstName: (string) (
                $data['first_name'] ?? ''
            ),

            middleName: (string) (
                $data['middle_name'] ?? ''
            ),

            lastName: (string) (
                $data['last_name'] ?? ''
            ),

            suffix: (string) (
                $data['suffix'] ?? ''
            ),

            relationship: (string) (
                $data['relationship'] ?? ''
            ),

            birthDate: (string) (
                $data['birth_date'] ?? ''
            ),

            remarks: (string) (
                $data['remarks'] ?? ''
            ),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'first_name' => $this->firstName,
            'middle_name' => $this->middleName,
            'last_name' => $this->lastName,
            'suffix' => $this->suffix,
            'relationship' => $this->relationship,
            'birth_date' => $this->birthDate,
            'remarks' => $this->remarks,
        ];
    }
}
