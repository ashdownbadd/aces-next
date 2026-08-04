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

    public function __construct(
        private readonly Database $database,
    ) {}

    public function run(): void
    {
        $files = glob(self::MIGRATION_PATH . '/*.php');

        sort($files);

        foreach ($files as $file) {

            $filename = basename($file);

            if ($filename === 'Migration.php') {
                continue;
            }

            require_once $file;

            $class = self::MIGRATION_NAMESPACE . pathinfo($filename, PATHINFO_FILENAME);

            if (! class_exists($class)) {
                throw new RuntimeException("Migration class [{$class}] not found.");
            }

            if ($this->hasRun($class)) {
                continue;
            }

            /** @var Migration $migration */
            $migration = new $class();

            $pdo = $this->database->connection();

            $pdo->beginTransaction();

            try {

                $migration->up($pdo);

                $this->record($class);

                $pdo->commit();

                echo "Migrated: {$class}" . PHP_EOL;
            } catch (\Throwable $exception) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                throw $exception;
            }
        }

        echo PHP_EOL . "Done." . PHP_EOL;
    }

    private function hasRun(string $migration): bool
    {
        $statement = $this->database
            ->connection()
            ->prepare(
                "
                SELECT COUNT(*)
                FROM migrations
                WHERE migration = :migration
                "
            );

        $statement->execute([
            'migration' => $migration,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    private function record(string $migration): void
    {
        $statement = $this->database
            ->connection()
            ->prepare(
                "
                INSERT INTO migrations (migration, batch)
                VALUES (:migration, :batch)
                "
            );

        $statement->execute([
            'migration' => $migration,
            'batch' => $this->nextBatch(),
        ]);
    }

    private function nextBatch(): int
    {
        $batch = $this->database
            ->connection()
            ->query("SELECT MAX(batch) FROM migrations")
            ->fetchColumn();

        return ((int) $batch) + 1;
    }
}
