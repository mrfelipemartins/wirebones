<?php

namespace MrFelipeMartins\Wirebones\Tests\Fixtures\Livewire;

use Livewire\Component;

final class UnmarkedComponent extends Component
{
    public function render(): string
    {
        return '<div>Unmarked</div>';
    }
}

