<?php

use MrFelipeMartins\Wirebones\Runtime\SkeletonRenderer;
use MrFelipeMartins\Wirebones\Support\WireboneDefinition;

function renderWirebone(array $wirebone, array $config = []): string
{
    foreach ($config as $key => $value) {
        config()->set("wirebones.{$key}", $value);
    }

    return app(SkeletonRenderer::class)->render(
        new WireboneDefinition(
            class: 'Tests\\Revenue',
            name: 'tests.revenue',
            route: '/dashboard',
            breakpoints: [375],
            wait: 0,
        ),
        $wirebone,
    );
}

it('renders safe root tags and falls back from unsafe tags', function (): void {
    $wirebone = [
        'rootTag' => 'section',
        'breakpoints' => [
            '375' => ['height' => 40, 'bones' => []],
        ],
    ];

    expect(renderWirebone($wirebone))->toContain('<section class="wirebones')
        ->and(renderWirebone([...$wirebone, 'rootTag' => 'script>alert(1)</script>']))
        ->toContain('<div class="wirebones');
});

it('renders breakpoint layers, dimensions, radius, and viewport media queries', function (): void {
    $html = renderWirebone([
        'rootTag' => 'div',
        'breakpoints' => [
            '375' => ['height' => 120, 'bones' => [[10.5, 12, 80.25, 20, 6]]],
            '768' => ['height' => 220, 'bones' => [[0, 30, 100, 50, '50%']]],
        ],
    ]);

    expect($html)->toContain('wirebones-layer-375')
        ->and($html)->toContain('height:120px')
        ->and($html)->toContain('@media (max-width: 767.98px)')
        ->and($html)->toContain('left:10.5%;top:12px;width:80.25%;height:20px;border-radius:6px;')
        ->and($html)->toContain('border-radius:50%;');
});

it('supports container queries when configured', function (): void {
    $html = renderWirebone([
        'breakpoints' => [
            '375' => ['height' => 40, 'bones' => []],
        ],
    ], ['responsive_strategy' => 'container']);

    expect($html)->toContain('container-type:inline-size')
        ->and($html)->toContain('@container');
});

it('skips or renders container bones based on configuration', function (): void {
    $wirebone = [
        'breakpoints' => [
            '375' => ['height' => 40, 'bones' => [[0, 0, 100, 40, 8, true], [10, 10, 20, 10, 4]]],
        ],
    ];

    expect(substr_count(renderWirebone($wirebone, ['render_containers' => false]), 'class="wirebones-bone'))->toBe(1)
        ->and(substr_count(renderWirebone($wirebone, ['render_containers' => true]), 'class="wirebones-bone'))->toBe(2);
});

it('renders color and animation configuration', function (): void {
    $html = renderWirebone([
        'breakpoints' => [
            '375' => ['height' => 40, 'bones' => [[0, 0, 100, 40, 999]]],
        ],
    ], [
        'color' => 'oklch(92.8% 0.006 264.531)',
        'dark_color' => 'oklch(37.3% 0.034 259.733)',
        'shimmer_color' => 'oklch(96.7% 0.003 264.542)',
        'animation' => 'shimmer',
        'speed' => '3s',
        'shimmer_angle' => 90,
        'stagger' => 25,
    ]);

    expect($html)->toContain('oklch(92.8% 0.006 264.531)')
        ->and($html)->toContain('oklch(37.3% 0.034 259.733)')
        ->and($html)->toContain('linear-gradient(90deg')
        ->and($html)->toContain('3s linear infinite')
        ->and($html)->toContain('--wirebones-delay:0ms');
});

it('normalizes invalid animation strategy and unsafe radius values', function (): void {
    $html = renderWirebone([
        'breakpoints' => [
            '375' => ['height' => 40, 'bones' => [[0, 0, 100, 40, 'url(javascript:alert(1))']]],
        ],
    ], [
        'animation' => 'invalid',
        'responsive_strategy' => 'invalid',
    ]);

    expect($html)->toContain('-pulse 1.8s')
        ->and($html)->toContain('border-radius:8px;')
        ->and($html)->toContain('@media');
});
