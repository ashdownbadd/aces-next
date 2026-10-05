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

    public function usernameExists(
        string $username,
        ?int $exceptUserId = null,
    ): bool {
        $sql = 'SELECT id FROM users WHERE username = :username';
        $parameters = ['username' => $username];

        if ($exceptUserId !== null) {
            $sql .= ' AND id <> :user_id';
            $parameters['user_id'] = $exceptUserId;
        }

        $sql .= ' LIMIT 1';

        $statement = $this->database
            ->connection()
            ->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function updateUsername(
        int $userId,
        string $username,
    ): void {
        $statement = $this->database
            ->connection()
            ->prepare(
                'UPDATE users
                 SET username = :username
                 WHERE id = :id
                 LIMIT 1'
            );

        $statement->execute([
            'username' => $username,
            'id' => $userId,
        ]);
    }

    public function updatePassword(
        int $userId,
        string $passwordHash,
    ): void {
        $statement = $this->database
            ->connection()
            ->prepare(
                'UPDATE users
                 SET password = :password
                 WHERE id = :id
                 LIMIT 1'
            );

        $statement->execute([
            'password' => $passwordHash,
            'id' => $userId,
        ]);
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
                    is_active,
                    role
                )
                VALUES
                (
                    :username,
                    :password,
                    :first_name,
                    :middle_name,
                    :last_name,
                    :is_active,
                    :role
                )'
            );

        $statement->execute([
            'username'    => $user->username(),
            'password'    => $user->password(),
            'first_name'  => $user->firstName(),
            'middle_name' => $user->middleName(),
            'last_name'   => $user->lastName(),
            'is_active'   => $user->isActive(),
            'role'        => $user->role(),
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
            role: isset($user['role'])
                ? (string) $user['role']
                : ((string) $user['username'] === 'admin'
                    ? 'admin'
                    : 'membership'),
        );
    }
}
