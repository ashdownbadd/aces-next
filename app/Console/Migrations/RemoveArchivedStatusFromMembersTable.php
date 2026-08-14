<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class RemoveArchivedStatusFromMembersTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            ALTER TABLE members
            MODIFY COLUMN status ENUM(
                'Pending',
                'Active',
                'Inactive'
            ) NOT NULL DEFAULT 'Pending';
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("
            ALTER TABLE members
            MODIFY COLUMN status ENUM(
                'Pending',
                'Active',
                'Inactive',
                'Archived'
            ) NOT NULL DEFAULT 'Pending';
        ");
    }
}
