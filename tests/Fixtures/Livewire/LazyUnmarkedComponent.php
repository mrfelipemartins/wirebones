<?php

namespace MrFelipeMartins\Wirebones\Tests\Fixtures\Livewire;

use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
final class LazyUnmarkedComponent extends Component
{
    public function render(): string
    {
        return <<<'BLADE'
            <section>
                <h2>Unmarked component loaded</h2>
            </section>
        BLADE;
    }
}

