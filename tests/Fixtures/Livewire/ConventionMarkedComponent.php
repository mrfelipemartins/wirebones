<?php

namespace MrFelipeMartins\Wirebones\Tests\Fixtures\Livewire;

use Livewire\Component;
use MrFelipeMartins\Wirebones\Attributes\Wirebone;

#[Wirebone(name: 'test.convention', route: '/wirebones-test')]
final class ConventionMarkedComponent extends Component
{
}
