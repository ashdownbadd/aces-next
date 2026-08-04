<?php

declare(strict_types=1);

namespace App\Console\Support;

use App\Console\Seeders\UserSeeder;
use App\Foundation\Database;

final readonly class SeederRunner
{
    public function __construct(
        private Database $database,
    ) {}

    public function run(): void
    {
        $seeders = [
            UserSeeder::class,
        ];

        foreach ($seeders as $seeder) {
            (new $seeder($this->database))->run();
        }
    }
}
