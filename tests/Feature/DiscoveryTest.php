<?php

use MrFelipeMartins\Wirebones\Discovery\WireboneDiscoverer;
use MrFelipeMartins\Wirebones\Tests\Fixtures\Livewire\LazyMarkedComponent;
use MrFelipeMartins\Wirebones\Tests\Fixtures\Livewire\MarkedCustomComponent;
use MrFelipeMartins\Wirebones\Tests\Fixtures\Livewire\MarkedDefaultComponent;
use MrFelipeMartins\Wirebones\Tests\Fixtures\Livewire\UnmarkedComponent;

it('discovers only concrete livewire components marked with wirebone', function (): void {
    config()->set('wirebones.breakpoints', [1024, 'bad', 320, 1024]);
    config()->set('wirebones.wait', 33);
    config()->set('wirebones.leaf_tags', ['p']);
    config()->set('wirebones.exclude_tags', ['script']);
    config()->set('wirebones.exclude_selectors', ['[hidden]']);

    $definitions = collect(app(WireboneDiscoverer::class)->discover())->keyBy->class;

    expect($definitions)->toHaveKeys([
        LazyMarkedComponent::class,
        MarkedCustomComponent::class,
        MarkedDefaultComponent::class,
    ])->not->toHaveKey(UnmarkedComponent::class);

    expect($definitions[MarkedDefaultComponent::class]->name)->toBe('marked-default-component')
        ->and($definitions[MarkedDefaultComponent::class]->breakpoints)->toBe([320, 1024])
        ->and($definitions[MarkedDefaultComponent::class]->wait)->toBe(33)
        ->and($definitions[MarkedDefaultComponent::class]->captureConfig)->toBe([
            'leafTags' => ['p'],
            'excludeTags' => ['script'],
            'excludeSelectors' => ['[hidden]'],
            'captureRoundedBorders' => true,
        ]);

    expect($definitions[MarkedCustomComponent::class]->name)->toBe('custom.card')
        ->and($definitions[MarkedCustomComponent::class]->route)->toBe('/custom-route')
        ->and($definitions[MarkedCustomComponent::class]->breakpoints)->toBe([375, 768, 1280])
        ->and($definitions[MarkedCustomComponent::class]->wait)->toBe(50)
        ->and($definitions[MarkedCustomComponent::class]->captureConfig)->toBe([
            'leafTags' => ['span'],
            'excludeTags' => ['svg'],
            'excludeSelectors' => ['[data-private]'],
            'captureRoundedBorders' => false,
        ]);
});

it('resolves a definition for a component object or class name', function (): void {
    $discoverer = app(WireboneDiscoverer::class);

    expect($discoverer->definitionForComponent(new MarkedCustomComponent())->name)->toBe('custom.card')
        ->and($discoverer->definitionForComponent(MarkedCustomComponent::class)->name)->toBe('custom.card')
        ->and($discoverer->definitionForComponent(UnmarkedComponent::class))->toBeNull()
        ->and($discoverer->definitionForComponent('Missing\\Component'))->toBeNull();
});

