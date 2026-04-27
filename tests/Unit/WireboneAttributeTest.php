<?php

use MrFelipeMartins\Wirebones\Attributes\Wirebone;

it('stores every explicit attribute option', function (): void {
    $attribute = new Wirebone(
        name: 'revenue-card',
        route: '/dashboard',
        breakpoints: [375, 768],
        wait: 250,
        leafTags: ['span'],
        excludeTags: ['svg'],
        excludeSelectors: ['[data-private]'],
        captureRoundedBorders: false,
    );

    expect($attribute->name)->toBe('revenue-card')
        ->and($attribute->route)->toBe('/dashboard')
        ->and($attribute->breakpoints)->toBe([375, 768])
        ->and($attribute->wait)->toBe(250)
        ->and($attribute->leafTags)->toBe(['span'])
        ->and($attribute->excludeTags)->toBe(['svg'])
        ->and($attribute->excludeSelectors)->toBe(['[data-private]'])
        ->and($attribute->captureRoundedBorders)->toBeFalse();
});

