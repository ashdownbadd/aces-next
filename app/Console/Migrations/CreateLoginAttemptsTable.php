<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class CreateLoginAttemptsTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE login_attempts (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                was_successful BOOLEAN NOT NULL DEFAULT FALSE,
                INDEX idx_login_attempts_lookup (username, ip_address, attempted_at),
                INDEX idx_login_attempts_ip (ip_address, attempted_at)
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci;"
        );
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS login_attempts');
    }
}
