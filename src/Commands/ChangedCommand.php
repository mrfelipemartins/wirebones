<?php

namespace MrFelipeMartins\Wirebones\Commands;

use Illuminate\Console\Command;
use MrFelipeMartins\Wirebones\Support\WireboneChangeDetector;

final class ChangedCommand extends Command
{
    protected $signature = 'wirebones:changed
        {path : Changed file path}
        {--json : Output JSON for tooling}';

    protected $description = 'Resolve Wirebones components affected by a changed file.';

    protected $hidden = true;

    public function handle(WireboneChangeDetector $changes): int
    {
        $path = $this->argument('path');
        $components = $changes->affectedComponents(is_string($path) ? $path : '');

        if ($this->option('json')) {
            $this->line((string) json_encode(['components' => $components], JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        foreach ($components as $component) {
            $this->line($component);
        }

        return self::SUCCESS;
    }
}
