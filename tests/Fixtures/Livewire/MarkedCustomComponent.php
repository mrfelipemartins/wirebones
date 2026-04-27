<?php

namespace MrFelipeMartins\Wirebones\Tests\Fixtures\Livewire;

use Livewire\Component;
use MrFelipeMartins\Wirebones\Attributes\Wirebone;

#[Wirebone(
    name: 'custom.card',
    route: '/custom-route',
    breakpoints: [1280, 375, 768, 375],
    wait: 50,
    leafTags: ['span'],
    excludeTags: ['svg'],
    excludeSelectors: ['[data-private]'],
    captureRoundedBorders: false,
)]
final class MarkedCustomComponent extends Component
{
    public function render(): string
    {
        return '<div>Custom</div>';
    }
}

