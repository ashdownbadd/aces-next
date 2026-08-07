<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class CreateMemberProfileTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE member_profile (

                member_id INT UNSIGNED PRIMARY KEY,

                first_name VARCHAR(100) NOT NULL,
                middle_name VARCHAR(100) NULL,
                last_name VARCHAR(100) NOT NULL,
                suffix VARCHAR(20) NULL,

                birth_date DATE NULL,
                birth_place VARCHAR(150) NULL,

                sex ENUM(
                    'Male',
                    'Female'
                ) NULL,

                civil_status ENUM(
                    'Single',
                    'Married',
                    'Widowed',
                    'Separated'
                ) NULL,

                nationality VARCHAR(100) NULL,

                CONSTRAINT fk_member_profile_member
                    FOREIGN KEY (member_id)
                    REFERENCES members(id)
                    ON DELETE CASCADE

            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci;
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("
            DROP TABLE IF EXISTS member_profile;
        ");
    }
}
