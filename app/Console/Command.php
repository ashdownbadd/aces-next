<?php

declare(strict_types=1);

namespace App\Console;

abstract class Command
{
    /**
     * The command name.
     *
     * Example:
     * migrate
     * user:create
     */
    abstract public function name(): string;

    /**
     * Short description displayed in the CLI help.
     */
    abstract public function description(): string;

    /**
     * Execute the command.
     *
     * Return:
     * 0 = success
     * 1 = failure
     */
    abstract public function handle(array $arguments = []): int;
}
