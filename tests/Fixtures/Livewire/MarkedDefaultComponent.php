<?php

namespace MrFelipeMartins\Wirebones\Tests\Fixtures\Livewire;

use Livewire\Component;
use MrFelipeMartins\Wirebones\Attributes\Wirebone;

#[Wirebone]
final class MarkedDefaultComponent extends Component
{
    public function render(): string
    {
        return '<div>Default</div>';
    }
}

