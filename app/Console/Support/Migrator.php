<?php

declare(strict_types=1);

namespace App\Console\Support;

use App\Console\Migrations\Migration;
use App\Foundation\Database;
use RuntimeException;

final class Migrator
{
    private const MIGRATION_NAMESPACE = 'App\\Console\\Migrations\\';

    private const MIGRATION_PATH = __DIR__ . '/../Migrations';

    /**
     * Parent tables must be created before tables
     * that reference them through foreign keys.
     */
    private const MIGRATION_ORDER = [
        'CreateUsersTable',
        'CreateMembersTable',

        'CreateMemberProfilesTable',
        'CreateMemberContactsTable',
        'CreateMemberAddressesTable',
        'CreateMemberEducationsTable',
        'CreateMemberLivelihoodsTable',
        'CreateMemberBeneficiariesTable',

        'CreateLoansTable',
        'CreateLoanAmortizationsTable',
        'CreateLoanPaymentsTable',
        'CreateLoanPaymentAllocationsTable',
    ];

    public function __construct(
        private readonly Database $database,
    ) {}

    /**
     * Run all pending migrations.
     */
    public function run(): void
    {
        $this->ensureMigrationsTable();

        $files = glob(
            self::MIGRATION_PATH . '/*.php'
        );

        if ($files === false) {
            throw new RuntimeException(
                'Unable to read migration directory.'
            );
        }

        $files = $this->sortMigrationFiles($files);

        foreach ($files as $file) {
            $filename = basename($file);

            /*
             * The abstract/base Migration class is not
             * an actual migration.
             */
            if ($filename === 'Migration.php') {
                continue;
            }

            require_once $file;

            $class = self::MIGRATION_NAMESPACE
                . pathinfo(
                    $filename,
                    PATHINFO_FILENAME
                );

            if (! class_exists($class)) {
                throw new RuntimeException(
                    "Migration class [{$class}] not found."
                );
            }

            if ($this->hasRun($class)) {
                continue;
            }

            /** @var Migration $migration */
            $migration = new $class();

            /*
             * IMPORTANT:
             *
             * Do NOT wrap CREATE TABLE / ALTER TABLE
             * migrations in a transaction.
             *
             * MySQL performs implicit commits for many
             * DDL statements.
             */
            $migration->up(
                $this->database->connection()
            );

            $this->record($class);

            echo "Migrated: {$class}" . PHP_EOL;
        }

        echo PHP_EOL . 'Done.' . PHP_EOL;
    }

    /**
     * Create the migration tracking table.
     */
    private function ensureMigrationsTable(): void
    {
        $this->database
            ->connection()
            ->exec(
                '
                CREATE TABLE IF NOT EXISTS migrations (

                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                    migration VARCHAR(255) NOT NULL UNIQUE,

                    batch INT UNSIGNED NOT NULL,

                    created_at TIMESTAMP NOT NULL
                        DEFAULT CURRENT_TIMESTAMP

                )
                ENGINE=InnoDB
                DEFAULT CHARSET=utf8mb4
                COLLATE=utf8mb4_unicode_ci
                '
            );
    }

    /**
     * Sort migration files according to their
     * dependency order.
     *
     * @param array<int, string> $files
     * @return array<int, string>
     */
    private function sortMigrationFiles(
        array $files
    ): array {
        usort(
            $files,
            function (
                string $first,
                string $second
            ): int {
                $firstClass = pathinfo(
                    $first,
                    PATHINFO_FILENAME
                );

                $secondClass = pathinfo(
                    $second,
                    PATHINFO_FILENAME
                );

                $firstOrder = array_search(
                    $firstClass,
                    self::MIGRATION_ORDER,
                    true
                );

                $secondOrder = array_search(
                    $secondClass,
                    self::MIGRATION_ORDER,
                    true
                );

                $firstOrder = $firstOrder === false
                    ? PHP_INT_MAX
                    : $firstOrder;

                $secondOrder = $secondOrder === false
                    ? PHP_INT_MAX
                    : $secondOrder;

                if ($firstOrder !== $secondOrder) {
                    return $firstOrder <=> $secondOrder;
                }

                return strcmp(
                    $firstClass,
                    $secondClass
                );
            }
        );

        return $files;
    }

    /**
     * Determine whether a migration has already run.
     */
    private function hasRun(
        string $migration
    ): bool {
        $statement = $this->database
            ->connection()
            ->prepare(
                '
                SELECT COUNT(*)
                FROM migrations
                WHERE migration = :migration
                '
            );

        $statement->execute([
            'migration' => $migration,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * Record a completed migration.
     */
    private function record(
        string $migration
    ): void {
        $statement = $this->database
            ->connection()
            ->prepare(
                '
                INSERT INTO migrations
                (
                    migration,
                    batch
                )
                VALUES
                (
                    :migration,
                    :batch
                )
                '
            );

        $statement->execute([
            'migration' => $migration,
            'batch' => $this->nextBatch(),
        ]);
    }

    /**
     * Get the next migration batch number.
     */
    private function nextBatch(): int
    {
        $batch = $this->database
            ->connection()
            ->query(
                'SELECT MAX(batch) FROM migrations'
            )
            ->fetchColumn();

        return ((int) $batch) + 1;
    }
}
