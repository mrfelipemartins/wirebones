<?php

use Illuminate\Support\Facades\Artisan;
use MrFelipeMartins\Wirebones\Tests\Fixtures\Livewire\DynamicViewMarkedComponent;
use MrFelipeMartins\Wirebones\Tests\Fixtures\Livewire\LazyMarkedComponent;
use MrFelipeMartins\Wirebones\Tests\Fixtures\Livewire\StaticViewMarkedComponent;
use MrFelipeMartins\Wirebones\Tests\Fixtures\Livewire\UnmarkedComponent;

function changedComponentsFor(string $path): array
{
    Artisan::call('wirebones:changed', [
        'path' => $path,
        '--json' => true,
    ]);

    $decoded = json_decode(Artisan::output(), true);

    return is_array($decoded) && isset($decoded['components']) && is_array($decoded['components'])
        ? $decoded['components']
        : [];
}

it('maps a changed wirebone component file to its component name', function (): void {
    expect(changedComponentsFor((string) (new ReflectionClass(LazyMarkedComponent::class))->getFileName()))
        ->toBe(['test.lazy-marked']);
});

it('maps a changed static blade view to its owning wirebone component', function (): void {
    expect(changedComponentsFor(__DIR__.'/../Fixtures/views/wirebones-fixtures/static-card.blade.php'))
        ->toBe(['test.static-view']);
});

it('maps a changed conventional livewire blade view to its owning wirebone component', function (): void {
    expect(changedComponentsFor(__DIR__.'/../Fixtures/views/livewire/convention-marked-component.blade.php'))
        ->toBe(['test.convention']);
});

it('ignores unmarked components and unrelated files', function (): void {
    $unrelated = $this->wirebonesOutputPath.'/unrelated.blade.php';
    file_put_contents($unrelated, '<div>Unrelated</div>');

    expect(changedComponentsFor((string) (new ReflectionClass(UnmarkedComponent::class))->getFileName()))->toBe([])
        ->and(changedComponentsFor($unrelated))->toBe([]);
});

it('ignores dynamic view expressions that cannot be statically mapped', function (): void {
    expect(changedComponentsFor(__DIR__.'/../Fixtures/views/wirebones-fixtures/dynamic-card.blade.php'))->toBe([])
        ->and(changedComponentsFor((string) (new ReflectionClass(DynamicViewMarkedComponent::class))->getFileName()))
        ->toBe(['test.dynamic-view']);
});
