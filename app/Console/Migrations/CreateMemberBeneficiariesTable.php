<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class CreateMemberBeneficiariesTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE member_beneficiaries (

                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                member_id INT UNSIGNED NOT NULL,

                first_name VARCHAR(100) NOT NULL,

                middle_name VARCHAR(100) NULL,

                last_name VARCHAR(100) NOT NULL,

                suffix VARCHAR(20) NULL,

                relationship VARCHAR(100) NOT NULL,

                birth_date DATE NULL,

                share_percentage DECIMAL(5,2) NOT NULL,

                remarks TEXT NULL,

                created_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP,

                updated_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,

                INDEX idx_member_beneficiary_member (
                    member_id
                ),

                CONSTRAINT fk_member_beneficiaries_member
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
            DROP TABLE IF EXISTS member_beneficiaries;
        ");
    }
}
