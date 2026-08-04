<?php

declare(strict_types=1);

namespace App\Domain\Members;

final readonly class Member
{
    public function __construct(
        private ?int $id,
        private string $memberNumber,
        private string $firstName,
        private ?string $middleName,
        private string $lastName,
        private ?string $birthDate,
        private ?string $sex,
        private ?string $civilStatus,
        private ?string $contactNumber,
        private ?string $email,
        private ?string $address,
        private string $membershipDate,
        private string $status,
    ) {}

    public function id(): ?int
    {
        return $this->id;
    }

    public function memberNumber(): string
    {
        return $this->memberNumber;
    }

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function middleName(): ?string
    {
        return $this->middleName;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function birthDate(): ?string
    {
        return $this->birthDate;
    }

    public function sex(): ?string
    {
        return $this->sex;
    }

    public function civilStatus(): ?string
    {
        return $this->civilStatus;
    }

    public function contactNumber(): ?string
    {
        return $this->contactNumber;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function address(): ?string
    {
        return $this->address;
    }

    public function membershipDate(): string
    {
        return $this->membershipDate;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function fullName(): string
    {
        return trim(
            implode(' ', array_filter([
                $this->firstName,
                $this->middleName,
                $this->lastName,
            ]))
        );
    }
}
