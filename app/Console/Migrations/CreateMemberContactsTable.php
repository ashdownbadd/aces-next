<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class CreateMemberContactsTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE member_contacts (

                member_id INT UNSIGNED PRIMARY KEY,

                mobile_number VARCHAR(20) NULL,
                telephone_number VARCHAR(30) NULL,
                email_address VARCHAR(150) NULL,

                created_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP,

                updated_at TIMESTAMP NOT NULL
                    DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,

                CONSTRAINT fk_member_contacts_member
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
            DROP TABLE IF EXISTS member_contacts;
        ");
    }
}
