<?php

declare(strict_types=1);

namespace App\Console\Seeders;

use App\Foundation\Database;

abstract class Seeder
{
    public function __construct(
        protected readonly Database $database,
    ) {}

    abstract public function run(): void;
}
