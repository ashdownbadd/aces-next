<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class AddUserRoles extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            "ALTER TABLE users
             ADD COLUMN role VARCHAR(30) NOT NULL DEFAULT 'operations'
             AFTER is_active"
        );

        $statement = $pdo->prepare(
            "UPDATE users SET role = 'admin' WHERE username = :username"
        );
        $statement->execute(['username' => getenv('SEED_ADMIN_USERNAME') ?: 'admin']);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('ALTER TABLE users DROP COLUMN role');
    }
}
