<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class AddReversalLinkToJournalVouchersTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            ALTER TABLE journal_vouchers
            ADD COLUMN reversal_of_voucher_id INT UNSIGNED NULL
                AFTER source_id,
            ADD INDEX idx_journal_vouchers_reversal_of (
                reversal_of_voucher_id
            ),
            ADD CONSTRAINT fk_journal_vouchers_reversal_of
                FOREIGN KEY (reversal_of_voucher_id)
                REFERENCES journal_vouchers(id)
                ON DELETE RESTRICT
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("
            ALTER TABLE journal_vouchers
            DROP FOREIGN KEY fk_journal_vouchers_reversal_of,
            DROP INDEX idx_journal_vouchers_reversal_of,
            DROP COLUMN reversal_of_voucher_id
        ");
    }
}
