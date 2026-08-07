<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class CreateMemberAddressesTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE member_addresses (

                member_id INT UNSIGNED PRIMARY KEY,

                house_number VARCHAR(100) NULL,
                street VARCHAR(150) NOT NULL,
                barangay VARCHAR(100) NOT NULL,
                city VARCHAR(100) NOT NULL,
                province VARCHAR(100) NOT NULL,
                zip_code VARCHAR(10) NOT NULL,

                created_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP,

                updated_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,

                CONSTRAINT fk_member_addresses_member
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
            DROP TABLE IF EXISTS member_addresses;
        ");
    }
}
