<?php

namespace MrFelipeMartins\Wirebones\Commands;

use Illuminate\Console\Command;
use MrFelipeMartins\Wirebones\Support\BoneRepository;

final class ClearCommand extends Command
{
    protected $signature = 'wirebones:clear';

    protected $description = 'Delete generated Wirebones skeleton files.';

    public function handle(BoneRepository $bones): int
    {
        $count = $bones->clear();

        $this->info("Deleted {$count} Wirebones file".($count === 1 ? '' : 's').'.');

        return self::SUCCESS;
    }
}
