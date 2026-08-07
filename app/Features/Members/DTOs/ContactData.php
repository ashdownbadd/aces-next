<?php

declare(strict_types=1);

namespace App\Features\Members\DTOs;

use App\Http\Request;

final class ContactData
{
    public function __construct(
        public readonly string $mobileNumber,
        public readonly string $telephoneNumber,
        public readonly string $emailAddress,
    ) {}

    public static function fromRequest(
        Request $request,
    ): self {
        return new self(
            mobileNumber: (string) $request->input(
                'mobile_number',
                '',
            ),

            telephoneNumber: (string) $request->input(
                'telephone_number',
                '',
            ),

            emailAddress: (string) $request->input(
                'email_address',
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
            mobileNumber: (string) (
                $data['mobile_number'] ?? ''
            ),

            telephoneNumber: (string) (
                $data['telephone_number'] ?? ''
            ),

            emailAddress: (string) (
                $data['email_address'] ?? ''
            ),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'mobile_number' => $this->mobileNumber,
            'telephone_number' => $this->telephoneNumber,
            'email_address' => $this->emailAddress,
        ];
    }
}
