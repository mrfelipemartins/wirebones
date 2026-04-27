<?php

use MrFelipeMartins\Wirebones\Support\BoneRepository;

it('leaves marked lazy components on normal livewire placeholders when no skeleton exists', function (): void {
    $response = $this->get('/wirebones-test');

    $response->assertOk()
        ->assertDontSee('wirebones-bone', false)
        ->assertDontSee('data-wirebone="test.lazy-marked"', false);
});

it('replaces marked lazy placeholders with generated skeletons and leaves unmarked placeholders alone', function (): void {
    app(BoneRepository::class)->putCompiled(
        'test.lazy-marked',
        '<section class="wirebones"><div class="wirebones-layer-375"><div class="wirebones-bone"></div></div></section>',
    );

    $response = $this->get('/wirebones-test');

    $response->assertOk()
        ->assertSee('wirebones-bone', false)
        ->assertSee('wirebones-layer-375', false)
        ->assertSee('x-intersect', false)
        ->assertSee('wirebones-test-unmarked', false);
});

it('prefers compiled placeholder blade files', function (): void {
    app(BoneRepository::class)->putCompiled(
        'test.lazy-marked',
        '<section class="wirebones compiled-wirebone"><div class="wirebones-bone"></div></section>',
    );

    $response = $this->get('/wirebones-test');

    $response->assertOk()
        ->assertSee('compiled-wirebone', false)
        ->assertSee('x-intersect', false)
        ->assertSee('wirebones-test-unmarked', false);
});

it('adds wirebone attributes to marked rendered component roots during build mode', function (): void {
    $response = $this->get('/wirebones-test?wirebones=1');

    $response->assertOk()
        ->assertSee('data-wirebone="test.lazy-marked"', false)
        ->assertSee('data-wirebone-class="MrFelipeMartins\Wirebones\\Tests\\Fixtures\\Livewire\\LazyMarkedComponent"', false)
        ->assertDontSee('data-wirebone-class="MrFelipeMartins\Wirebones\\Tests\\Fixtures\\Livewire\\LazyUnmarkedComponent"', false);
});
