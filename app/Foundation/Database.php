<?php

declare(strict_types=1);

namespace App\Foundation;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private PDO $connection;

    public function __construct(
        private readonly Config $config,
    ) {
        $this->connect();
    }

    private function connect(): void
    {
        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config->get('database.driver'),
            $this->config->get('database.host'),
            $this->config->get('database.port'),
            $this->config->get('database.database'),
            $this->config->get('database.charset'),
        );

        try {
            $this->connection = new PDO(
                $dsn,
                $this->config->get('database.username'),
                $this->config->get('database.password'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Unable to connect to the database.',
                previous: $exception,
            );
        }
    }

    public function connection(): PDO
    {
        return $this->connection;
    }
}
