<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class UpdateCooperativeStaffRoles extends Migration
{
    public function up(PDO $pdo): void
    {
        // The old operational role now represents the Membership Officer.
        $pdo->exec(
            "UPDATE users
             SET role = 'membership'
             WHERE role = 'operations'"
        );

        // New users default to Membership unless their role is explicitly set.
        $pdo->exec(
            "ALTER TABLE users
             ALTER COLUMN role SET DEFAULT 'membership'"
        );
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec(
            "UPDATE users
             SET role = 'operations'
             WHERE role = 'membership'"
        );

        $pdo->exec(
            "ALTER TABLE users
             ALTER COLUMN role SET DEFAULT 'operations'"
        );
    }
}
