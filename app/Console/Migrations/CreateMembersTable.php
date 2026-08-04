<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class CreateMembersTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            "
            CREATE TABLE members (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                member_number VARCHAR(30) NOT NULL UNIQUE,

                first_name VARCHAR(100) NOT NULL,
                middle_name VARCHAR(100) NULL,
                last_name VARCHAR(100) NOT NULL,

                birth_date DATE NULL,

                sex ENUM('Male', 'Female') NULL,

                civil_status ENUM(
                    'Single',
                    'Married',
                    'Widowed',
                    'Separated'
                ) NULL,

                contact_number VARCHAR(30) NULL,
                email VARCHAR(150) NULL,

                address TEXT NULL,

                membership_date DATE NOT NULL,

                status ENUM(
                    'Pending',
                    'Active',
                    'Inactive',
                    'Archived'
                ) NOT NULL DEFAULT 'Pending',

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
            DROP TABLE IF EXISTS members;
            "
        );
    }
}
