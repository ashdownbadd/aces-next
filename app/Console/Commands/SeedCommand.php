<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use App\Console\Support\SeederRunner;

final class SeedCommand extends Command
{
    public function __construct(
        private readonly SeederRunner $runner,
    ) {}

    public function name(): string
    {
        return 'db:seed';
    }

    public function description(): string
    {
        return 'Seed the database.';
    }

    public function handle(array $arguments = []): int
    {
        $this->runner->run();

        return 0;
    }
}
