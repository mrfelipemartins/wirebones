<?php

namespace MrFelipeMartins\Wirebones\Tests\Fixtures\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use MrFelipeMartins\Wirebones\Attributes\Wirebone;

#[Wirebone(name: 'test.static-view', route: '/wirebones-test')]
final class StaticViewMarkedComponent extends Component
{
    public function render(): View
    {
        return view('wirebones-fixtures.static-card');
    }
}
