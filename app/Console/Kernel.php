<?php

declare(strict_types=1);

namespace App\Console;

final class Kernel
{
    /**
     * @var array<string, Command>
     */
    private array $commands = [];

    public function register(Command $command): self
    {
        $this->commands[$command->name()] = $command;

        return $this;
    }

    public function handle(array $arguments): int
    {
        $name = $arguments[1] ?? '';

        if (! isset($this->commands[$name])) {
            $this->showHelp();

            return 1;
        }

        return $this->commands[$name]
            ->handle(array_slice($arguments, 2));
    }

    private function showHelp(): void
    {
        echo "ACES CLI" . PHP_EOL;
        echo PHP_EOL;
        echo "Available commands:" . PHP_EOL;
        echo PHP_EOL;

        foreach ($this->commands as $command) {
            printf(
                "  %-20s %s" . PHP_EOL,
                $command->name(),
                $command->description(),
            );
        }

        echo PHP_EOL;
    }
}
