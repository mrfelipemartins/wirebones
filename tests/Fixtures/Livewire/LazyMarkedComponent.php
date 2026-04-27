<?php

namespace MrFelipeMartins\Wirebones\Tests\Fixtures\Livewire;

use Livewire\Attributes\Lazy;
use Livewire\Component;
use MrFelipeMartins\Wirebones\Attributes\Wirebone;

#[Lazy]
#[Wirebone(name: 'test.lazy-marked', route: '/wirebones-test', wait: 25)]
final class LazyMarkedComponent extends Component
{
    public function render(): string
    {
        return <<<'BLADE'
            <section>
                <h2>Marked component loaded</h2>
            </section>
        BLADE;
    }
}

