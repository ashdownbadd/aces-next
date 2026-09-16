<?php

declare(strict_types=1);

namespace App\Console\Seeders;

use PDO;
use RuntimeException;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        $statement = $this->database
            ->connection()
            ->prepare(
                '
                SELECT id
                FROM users
                WHERE username = :username
                LIMIT 1
                '
            );

        $statement->execute([
            'username' => getenv('SEED_ADMIN_USERNAME') ?: 'admin',
        ]);

        if ($statement->fetch(PDO::FETCH_ASSOC) !== false) {
            return;
        }

        $plainPassword = getenv('SEED_ADMIN_PASSWORD') ?: '';

        if (strlen($plainPassword) < 12) {
            throw new RuntimeException(
                'SEED_ADMIN_PASSWORD must be configured with at least 12 characters.',
            );
        }

        $password = password_hash(
            $plainPassword,
            PASSWORD_DEFAULT,
        );

        $statement = $this->database
            ->connection()
            ->prepare(
                '
                INSERT INTO users
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
                )
                '
            );

        $statement->execute([
            'username'    => 'admin',
            'password'    => $password,
            'first_name'  => 'System',
            'middle_name' => null,
            'last_name'   => 'Administrator',
            'is_active'   => true,
        ]);
    }
}
