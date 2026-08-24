<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Seeders\LedgerSeeder;
use App\Console\Command;
use App\Foundation\Database;

final class SeedLedgerCommand extends Command
{
    public function __construct(
        private readonly Database $database,
    ) {}

    public function name(): string
    {
        return 'db:seed-ledger';
    }

    public function description(): string
    {
        return 'Seed the Ledger Chart of Accounts.';
    }

    public function handle(array $arguments = []): int
    {
        (new LedgerSeeder($this->database))->run();

        return 0;
    }
}
