<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;

final class AddEducationDetailsToMemberEducationsTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->exec('
            ALTER TABLE member_educations
                ADD COLUMN school_name VARCHAR(150) NULL AFTER highest_educational_attainment,
                ADD COLUMN graduation_year SMALLINT UNSIGNED NULL AFTER school_name
        ');
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('
            ALTER TABLE member_educations
                DROP COLUMN graduation_year,
                DROP COLUMN school_name
        ');
    }
}
