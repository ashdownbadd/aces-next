<?php

declare(strict_types=1);

namespace App\Features\Authentication\Repositories;

use App\Domain\Authentication\User;
use App\Foundation\Database;
use PDO;

final readonly class UserRepository
{
    public function __construct(
        private Database $database,
    ) {}

    public function findById(int $id): ?User
    {
        $statement = $this->database
            ->connection()
            ->prepare(
                'SELECT * FROM users WHERE id = :id LIMIT 1'
            );

        $statement->execute([
            'id' => $id,
        ]);

        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if ($user === false) {
            return null;
        }

        return $this->map($user);
    }

    public function findByUsername(string $username): ?User
    {
        $statement = $this->database
            ->connection()
            ->prepare(
                'SELECT * FROM users WHERE username = :username LIMIT 1'
            );

        $statement->execute([
            'username' => $username,
        ]);

        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if ($user === false) {
            return null;
        }

        return $this->map($user);
    }

    public function create(User $user): int
    {
        $statement = $this->database
            ->connection()
            ->prepare(
                'INSERT INTO users
                (
                    username,
                    password,
                    first_name,
                    middle_name,
                    last_name,
                    is_active
                )
                VALUES
                (
                    :username,
                    :password,
                    :first_name,
                    :middle_name,
                    :last_name,
                    :is_active
                )'
            );

        $statement->execute([
            'username'    => $user->username(),
            'password'    => $user->password(),
            'first_name'  => $user->firstName(),
            'middle_name' => $user->middleName(),
            'last_name'   => $user->lastName(),
            'is_active'   => $user->isActive(),
        ]);

        return (int) $this->database
            ->connection()
            ->lastInsertId();
    }

    private function map(array $user): User
    {
        return new User(
            id: (int) $user['id'],
            username: $user['username'],
            password: $user['password'],
            firstName: $user['first_name'],
            middleName: $user['middle_name'],
            lastName: $user['last_name'],
            isActive: (bool) $user['is_active'],
        );
    }
}
