<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class CreateActivityLogsTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE activity_logs (

                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                user_id INT UNSIGNED NULL,

                action VARCHAR(100) NOT NULL,

                description TEXT NULL,

                subject_type VARCHAR(100) NULL,

                subject_id INT UNSIGNED NULL,

                ip_address VARCHAR(45) NULL,

                created_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP,

                INDEX idx_activity_logs_user (
                    user_id
                ),

                INDEX idx_activity_logs_action (
                    action
                ),

                INDEX idx_activity_logs_subject (
                    subject_type,
                    subject_id
                ),

                INDEX idx_activity_logs_created_at (
                    created_at
                ),

                CONSTRAINT fk_activity_logs_user
                    FOREIGN KEY (user_id)
                    REFERENCES users(id)
                    ON DELETE SET NULL

            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci;
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("
            DROP TABLE IF EXISTS activity_logs;
        ");
    }
}
