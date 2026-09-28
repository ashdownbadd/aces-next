<?php

declare(strict_types=1);

namespace App\Console\Migrations;

use PDO;
use RuntimeException;

final class RenameOperationsRoleToMembership extends Migration
{
    public function up(PDO $pdo): void
    {
        $pdo->beginTransaction();

        try {
            $membershipUsername = $pdo->prepare(
                'SELECT id FROM users WHERE username = :username LIMIT 1'
            );
            $membershipUsername->execute(['username' => 'membership']);
            $membershipExists = $membershipUsername->fetchColumn() !== false;

            $operationsUsername = $pdo->prepare(
                'SELECT id FROM users WHERE username = :username LIMIT 1'
            );
            $operationsUsername->execute(['username' => 'operations']);
            $operationsId = $operationsUsername->fetchColumn();

            if ($operationsId !== false) {
                if ($membershipExists) {
                    throw new RuntimeException(
                        'Cannot rename the operations account to membership because username "membership" already exists.'
                    );
                }

                $rename = $pdo->prepare(
                    'UPDATE users SET username = :new_username WHERE id = :id'
                );
                $rename->execute([
                    'new_username' => 'membership',
                    'id' => (int) $operationsId,
                ]);
            }

            $pdo->exec(
                "UPDATE users SET role = 'membership' WHERE role = 'operations'"
            );

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public function down(PDO $pdo): void
    {
        $statement = $pdo->prepare(
            "UPDATE users SET role = 'operations' WHERE role = 'membership'"
        );
        $statement->execute();
    }
}
