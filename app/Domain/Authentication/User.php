<?php

declare(strict_types=1);

namespace App\Domain\Authentication;

final readonly class User
{
    public function __construct(
        private ?int $id,
        private string $username,
        private string $password,
        private string $firstName,
        private ?string $middleName,
        private string $lastName,
        private bool $isActive,
    ) {}

    public function id(): ?int
    {
        return $this->id;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function password(): string
    {
        return $this->password;
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

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
