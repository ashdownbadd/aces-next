<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class CreateMemberNumberSequenceTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            '
            CREATE TABLE member_number_sequences (
                id TINYINT UNSIGNED PRIMARY KEY,
                next_number INT UNSIGNED NOT NULL,

                CONSTRAINT chk_member_number_sequence_singleton
                    CHECK (id = 1)
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci
            '
        );

        $nextNumber = (int) (
            $pdo->query(
                "
                SELECT COALESCE(
                    MAX(CAST(member_number AS UNSIGNED)),
                    0
                ) + 1
                FROM members
                WHERE member_number REGEXP '^[0-9]{1,4}$'
                "
            )->fetchColumn()
        );

        if ($nextNumber < 1 || $nextNumber > 9999) {
            throw new \RuntimeException(
                'Unable to initialize the member number sequence.'
            );
        }

        $statement = $pdo->prepare(
            '
            INSERT INTO member_number_sequences
            (
                id,
                next_number
            )
            VALUES
            (
                1,
                :next_number
            )
            '
        );

        $statement->execute([
            'next_number' => $nextNumber,
        ]);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec(
            'DROP TABLE IF EXISTS member_number_sequences'
        );
    }
}
