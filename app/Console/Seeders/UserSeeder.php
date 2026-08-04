<?php

declare(strict_types=1);

namespace App\Console\Seeders;

use PDO;

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
            'username' => 'admin',
        ]);

        if ($statement->fetch(PDO::FETCH_ASSOC) !== false) {
            return;
        }

        $password = password_hash(
            'admin123',
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
