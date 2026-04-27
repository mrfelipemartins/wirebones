<?php

use MrFelipeMartins\Wirebones\Support\WireboneDefinition;

it('serializes the capture payload shape', function (): void {
    $definition = new WireboneDefinition(
        class: 'App\\Livewire\\Revenue',
        name: 'revenue',
        route: '/dashboard',
        breakpoints: [375, 1280],
        wait: 100,
        file: '/app/Livewire/Revenue.php',
        captureConfig: [
            'leafTags' => ['span'],
            'excludeTags' => ['svg'],
            'excludeSelectors' => ['[data-ignore]'],
            'captureRoundedBorders' => false,
        ],
    );

    expect($definition->jsonSerialize())->toBe([
        'class' => 'App\\Livewire\\Revenue',
        'name' => 'revenue',
        'route' => '/dashboard',
        'breakpoints' => [375, 1280],
        'wait' => 100,
        'file' => '/app/Livewire/Revenue.php',
        'captureConfig' => [
            'leafTags' => ['span'],
            'excludeTags' => ['svg'],
            'excludeSelectors' => ['[data-ignore]'],
            'captureRoundedBorders' => false,
        ],
    ]);
});

