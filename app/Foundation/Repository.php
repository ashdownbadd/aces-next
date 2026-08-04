<?php

declare(strict_types=1);

namespace App\Foundation;

use PDO;

abstract class Repository
{
    public function __construct(
        protected readonly Database $database,
    ) {}

    protected function connection(): PDO
    {
        return $this->database->connection();
    }
}
