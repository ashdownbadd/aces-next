<?php

declare(strict_types=1);

namespace App\Console\Support;

use App\Console\Migrations\Migration;
use App\Foundation\Database;
use PDO;
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
        'AddUserRoles',
        'CreateLoginAttemptsTable',

        'CreateAccountsTable',
        'CreateActivityLogsTable',
        'CreateJournalVouchersTable',
        'CreateJournalLinesTable',
        'AddPostedStatusToJournalVouchersTable',
        'AddReversalLinkToJournalVouchersTable',

        'CreateMembersTable',
        'CreateMemberNumberSequenceTable',
        'CreateMemberProfilesTable',
        'CreateMemberContactsTable',
        'CreateMemberAddressesTable',
        'CreateMemberEducationsTable',
        'AddEducationDetailsToMemberEducationsTable',
        'CreateMemberLivelihoodsTable',
        'CreateMemberBeneficiariesTable',
        'RemoveArchivedStatusFromMembersTable',
        'RemoveSharePercentageFromMemberBeneficiariesTable',

        'CreateLoansTable',
        'CreateLoanAmortizationsTable',
        'CreateLoanPaymentsTable',
        'CreateLoanPaymentAllocationsTable',
        'AddPaymentReversalFields',
        'AddPaymentIdempotencyAndUnappliedAccount',
        'EnforceFinancialDataIntegrity',
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

            /*
             * Existing installations may predate the migration
             * tracking entries while already containing some of the
             * newer schema changes (for example, after a manual SQL
             * update or a previous security-fix build). Re-running a
             * CREATE/ALTER migration in that state would fail even
             * though the intended schema change is already present.
             *
             * Reconcile only migrations for which we can prove the
             * complete schema change is already present. Migrations
             * that are only partially applied are still executed.
             */
            if ($this->isAppliedInSchema($class)) {
                $this->record($class);
                echo "Reconciled: {$class}" . PHP_EOL;
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
     * Determine whether an unrecorded migration's complete schema
     * change is already present. This is intentionally explicit rather
     * than a generic table-existence heuristic so a partially applied
     * migration is never silently skipped.
     */
    private function isAppliedInSchema(string $migration): bool
    {
        $pdo = $this->database->connection();

        return match ($migration) {
            self::MIGRATION_NAMESPACE . 'CreateMemberNumberSequenceTable'
                => $this->tableExists($pdo, 'member_number_sequences'),

            self::MIGRATION_NAMESPACE . 'AddEducationDetailsToMemberEducationsTable'
                => $this->columnsExist(
                    $pdo,
                    'member_educations',
                    ['school_name', 'graduation_year'],
                ),

            self::MIGRATION_NAMESPACE . 'AddUserRoles'
                => $this->columnExists($pdo, 'users', 'role'),

            self::MIGRATION_NAMESPACE . 'CreateLoginAttemptsTable'
                => $this->tableExists($pdo, 'login_attempts'),

            self::MIGRATION_NAMESPACE . 'AddPaymentIdempotencyAndUnappliedAccount'
                => $this->columnExists(
                    $pdo,
                    'loan_payments',
                    'idempotency_key',
                )
                && $this->indexExists(
                    $pdo,
                    'loan_payments',
                    'uq_loan_payments_idempotency',
                )
                && $this->accountExists($pdo, '2030'),

            self::MIGRATION_NAMESPACE . 'EnforceFinancialDataIntegrity'
                => $this->constraintsExist(
                    $pdo,
                    [
                        'loans' => 'chk_loans_financial_values',
                        'loan_amortizations' => 'chk_loan_amortizations_amounts',
                        'loan_payments' => 'chk_loan_payments_amounts',
                        'loan_payment_allocations' => 'chk_loan_payment_allocation_amount',
                        'journal_lines' => 'chk_journal_lines_nonnegative',
                    ],
                ),

            default => false,
        };
    }

    private function tableExists(PDO $pdo, string $table): bool
    {
        $statement = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = :table'
        );
        $statement->execute(['table' => $table]);

        return (int) $statement->fetchColumn() > 0;
    }

    private function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $statement = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = :table
               AND column_name = :column'
        );
        $statement->execute([
            'table' => $table,
            'column' => $column,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * @param array<int, string> $columns
     */
    private function columnsExist(
        PDO $pdo,
        string $table,
        array $columns,
    ): bool {
        foreach ($columns as $column) {
            if (! $this->columnExists($pdo, $table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function indexExists(
        PDO $pdo,
        string $table,
        string $index,
    ): bool {
        $statement = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = :table
               AND index_name = :index'
        );
        $statement->execute([
            'table' => $table,
            'index' => $index,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    private function accountExists(PDO $pdo, string $accountCode): bool
    {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM accounts WHERE account_code = :account_code'
        );
        $statement->execute(['account_code' => $accountCode]);

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * @param array<string, string> $constraints
     */
    private function constraintsExist(
        PDO $pdo,
        array $constraints,
    ): bool {
        foreach ($constraints as $table => $constraint) {
            $statement = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM information_schema.table_constraints
                 WHERE constraint_schema = DATABASE()
                   AND table_name = :table
                   AND constraint_name = :constraint'
            );
            $statement->execute([
                'table' => $table,
                'constraint' => $constraint,
            ]);

            if ((int) $statement->fetchColumn() === 0) {
                return false;
            }
        }

        return true;
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
