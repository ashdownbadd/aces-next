<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class CreateMemberLivelihoodsTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE member_livelihoods (

                member_id INT UNSIGNED PRIMARY KEY,

                employment_status VARCHAR(50) NULL,

                occupation VARCHAR(150) NULL,

                employer VARCHAR(150) NULL,

                monthly_income DECIMAL(12,2) NULL,

                created_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP,

                updated_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,

                CONSTRAINT fk_member_livelihoods_member
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
            DROP TABLE IF EXISTS member_livelihoods;
        ");
    }
}
