<?php

namespace MrFelipeMartins\Wirebones\Tests\Fixtures\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use MrFelipeMartins\Wirebones\Attributes\Wirebone;

#[Wirebone(name: 'test.dynamic-view', route: '/wirebones-test')]
final class DynamicViewMarkedComponent extends Component
{
    public function render(): View
    {
        return view($this->viewName());
    }

    private function viewName(): string
    {
        return 'wirebones-fixtures.dynamic-card';
    }
}
