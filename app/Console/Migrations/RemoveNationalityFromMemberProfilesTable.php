<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class RemoveNationalityFromMemberProfilesTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            ALTER TABLE member_profiles
            DROP COLUMN nationality;
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("
            ALTER TABLE member_profiles
            ADD COLUMN nationality VARCHAR(100) NULL
            AFTER civil_status;
        ");
    }
}
