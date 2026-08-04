<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class CreateUsersTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            "
            CREATE TABLE users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,

                first_name VARCHAR(100) NOT NULL,
                middle_name VARCHAR(100) NULL,
                last_name VARCHAR(100) NOT NULL,

                is_active BOOLEAN NOT NULL DEFAULT TRUE,

                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci;
            "
        );
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec(
            "
            DROP TABLE IF EXISTS users;
            "
        );
    }
}
