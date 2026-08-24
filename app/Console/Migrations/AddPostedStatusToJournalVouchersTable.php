<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class AddPostedStatusToJournalVouchersTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            ALTER TABLE journal_vouchers
            MODIFY status ENUM(
                'Pending',
                'Approved',
                'Rejected',
                'Posted'
            ) NOT NULL DEFAULT 'Pending'
        ");

        $pdo->exec("
            ALTER TABLE journal_vouchers
            ADD COLUMN posted_by INT UNSIGNED NULL AFTER approved_by,
            ADD COLUMN posted_at TIMESTAMP NULL AFTER approved_at,
            ADD INDEX idx_journal_vouchers_posted_by (posted_by),
            ADD CONSTRAINT fk_journal_vouchers_posted_by
                FOREIGN KEY (posted_by)
                REFERENCES users(id)
                ON DELETE SET NULL
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("
            ALTER TABLE journal_vouchers
            DROP FOREIGN KEY fk_journal_vouchers_posted_by,
            DROP INDEX idx_journal_vouchers_posted_by,
            DROP COLUMN posted_at,
            DROP COLUMN posted_by
        ");

        $pdo->exec("
            ALTER TABLE journal_vouchers
            MODIFY status ENUM(
                'Pending',
                'Approved',
                'Rejected'
            ) NOT NULL DEFAULT 'Pending'
        ");
    }
}
