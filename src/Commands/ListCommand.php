<?php

namespace MrFelipeMartins\Wirebones\Commands;

use Illuminate\Console\Command;
use MrFelipeMartins\Wirebones\Discovery\WireboneDiscoverer;

final class ListCommand extends Command
{
    protected $signature = 'wirebones:list';

    protected $description = 'List Livewire components marked with #[Wirebone].';

    public function handle(WireboneDiscoverer $discoverer): int
    {
        $definitions = $discoverer->discover();

        if ($definitions === []) {
            $this->warn('No #[Wirebone] components found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Name', 'Class', 'Route', 'Breakpoints', 'Wait'],
            collect($definitions)->map(fn ($definition): array => [
                $definition->name,
                $definition->class,
                $definition->route ?? '<missing>',
                implode(', ', $definition->breakpoints),
                $definition->wait.'ms',
            ])->all(),
        );

        return self::SUCCESS;
    }
}
