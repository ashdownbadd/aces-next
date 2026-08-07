<?php

declare(strict_types=1);

namespace App\Features\Members\DTOs;

use App\Http\Request;

final class AddressData
{
    public function __construct(
        public readonly string $houseNumber,
        public readonly string $street,
        public readonly string $barangay,
        public readonly string $city,
        public readonly string $province,
        public readonly string $zipCode,
    ) {}

    public static function fromRequest(
        Request $request,
    ): self {
        return new self(
            houseNumber: (string) $request->input(
                'house_number',
                '',
            ),

            street: (string) $request->input(
                'street',
                '',
            ),

            barangay: (string) $request->input(
                'barangay',
                '',
            ),

            city: (string) $request->input(
                'city',
                '',
            ),

            province: (string) $request->input(
                'province',
                '',
            ),

            zipCode: (string) $request->input(
                'zip_code',
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
            houseNumber: (string) (
                $data['house_number'] ?? ''
            ),

            street: (string) (
                $data['street'] ?? ''
            ),

            barangay: (string) (
                $data['barangay'] ?? ''
            ),

            city: (string) (
                $data['city'] ?? ''
            ),

            province: (string) (
                $data['province'] ?? ''
            ),

            zipCode: (string) (
                $data['zip_code'] ?? ''
            ),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'house_number' => $this->houseNumber,
            'street' => $this->street,
            'barangay' => $this->barangay,
            'city' => $this->city,
            'province' => $this->province,
            'zip_code' => $this->zipCode,
        ];
    }
}
