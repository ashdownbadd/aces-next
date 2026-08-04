<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use App\Console\Support\Migrator;

final class MigrateCommand extends Command
{
    public function __construct(
        private readonly Migrator $migrator,
    ) {}

    public function name(): string
    {
        return 'migrate';
    }

    public function description(): string
    {
        return 'Run all pending database migrations.';
    }

    public function handle(array $arguments = []): int
    {
        $this->migrator->run();

        return 0;
    }
}
