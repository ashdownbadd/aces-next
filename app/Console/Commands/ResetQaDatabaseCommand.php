<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use App\Foundation\Database;
use RuntimeException;

final class ResetQaDatabaseCommand extends Command
{
    public function __construct(
        private readonly Database $database,
    ) {}

    public function name(): string
    {
        return 'db:reset-qa';
    }

    public function description(): string
    {
        return 'Reset development/QA data and recreate the four test users.';
    }

    public function handle(array $arguments = []): int
    {
        $pdo = $this->database->connection();

        try {
            /*
             * TRUNCATE is DDL in MySQL/MariaDB and implicitly commits.
             * Therefore this command intentionally does not wrap TRUNCATE
             * operations in a PDO transaction.
             */
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

            $tables = [
                'activity_logs',
                'journal_lines',
                'journal_vouchers',
                'loan_payment_allocations',
                'loan_payments',
                'loan_amortizations',
                'loans',
                'member_addresses',
                'member_beneficiaries',
                'member_contacts',
                'member_educations',
                'member_livelihoods',
                'member_profiles',
                'members',
                'login_attempts',
                'users',
            ];

            foreach ($tables as $table) {
                $pdo->exec("TRUNCATE TABLE `{$table}`");
            }

            /*
             * Reset the member-number sequence without blindly inserting
             * a duplicate singleton row.
             */
            $pdo->exec(
                'UPDATE member_number_sequences
                 SET next_number = 1
                 WHERE id = 1'
            );

            $sequenceExists = (int) $pdo->query(
                'SELECT COUNT(*)
                 FROM member_number_sequences
                 WHERE id = 1'
            )->fetchColumn();

            if ($sequenceExists === 0) {
                $pdo->exec(
                    'INSERT INTO member_number_sequences
                     (id, next_number)
                     VALUES (1, 1)'
                );
            }

            $users = [
                [
                    'username' => 'admin',
                    'password' => 'admin',
                    'first_name' => 'Randall',
                    'middle_name' => 'Jay V.',
                    'last_name' => 'Unarce',
                    'role' => 'admin',
                ],
                [
                    'username' => 'membership',
                    'password' => 'membership',
                    'first_name' => 'Justin',
                    'middle_name' => 'Drew',
                    'last_name' => 'Bieber',
                    'role' => 'membership',
                ],
                [
                    'username' => 'accounting',
                    'password' => 'accounting',
                    'first_name' => 'Kassie',
                    'middle_name' => 'Anne',
                    'last_name' => 'Crisostomo',
                    'role' => 'accounting',
                ],
                [
                    'username' => 'loan',
                    'password' => 'loan',
                    'first_name' => 'Hailey',
                    'middle_name' => 'B.',
                    'last_name' => 'Bieber',
                    'role' => 'loan_officer',
                ],
            ];

            $statement = $pdo->prepare(
                'INSERT INTO users
                (
                    username,
                    password,
                    first_name,
                    middle_name,
                    last_name,
                    role,
                    is_active
                )
                VALUES
                (
                    :username,
                    :password,
                    :first_name,
                    :middle_name,
                    :last_name,
                    :role,
                    :is_active
                )'
            );

            foreach ($users as $user) {
                $statement->execute([
                    'username' => $user['username'],
                    'password' => password_hash(
                        $user['password'],
                        PASSWORD_DEFAULT,
                    ),
                    'first_name' => $user['first_name'],
                    'middle_name' => $user['middle_name'],
                    'last_name' => $user['last_name'],
                    'role' => $user['role'],
                    'is_active' => 1,
                ]);
            }

            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

            echo 'QA database reset. Created 4 users and cleared transactional/test data.'
                . PHP_EOL;

            return 0;
        } catch (\Throwable $exception) {
            try {
                $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            } catch (\Throwable) {
                // Preserve the original failure.
            }

            throw new RuntimeException(
                'QA database reset failed: ' . $exception->getMessage(),
                previous: $exception,
            );
        }
    }
}
