<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class RemoveSharePercentageFromMemberBeneficiariesTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            ALTER TABLE member_beneficiaries
            DROP COLUMN share_percentage;
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("
            ALTER TABLE member_beneficiaries
            ADD COLUMN share_percentage DECIMAL(5,2) NOT NULL
            DEFAULT 0.00
            AFTER birth_date;
        ");
    }
}
