<?php

use Illuminate\Routing\Router;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use MrFelipeMartins\Wirebones\Commands\BuildCommand;
use MrFelipeMartins\Wirebones\Commands\ChangedCommand;
use MrFelipeMartins\Wirebones\Commands\ClearCommand;
use MrFelipeMartins\Wirebones\Commands\ListCommand;
use MrFelipeMartins\Wirebones\Runtime\BuildModeMiddleware;
use MrFelipeMartins\Wirebones\Support\BoneRepository;

it('merges config and loads placeholder views', function (): void {
    expect(config('wirebones.output_path'))->toBe($this->wirebonesOutputPath)
        ->and(view()->exists('wirebones::placeholder'))->toBeTrue();
});

it('registers console commands', function (): void {
    expect(app(ConsoleKernel::class)->all())->toHaveKeys([
        'wirebones:build',
        'wirebones:changed',
        'wirebones:clear',
        'wirebones:list',
    ]);

    expect(app(BuildCommand::class))->toBeInstanceOf(BuildCommand::class)
        ->and(app(ChangedCommand::class))->toBeInstanceOf(ChangedCommand::class)
        ->and(app(ClearCommand::class))->toBeInstanceOf(ClearCommand::class)
        ->and(app(ListCommand::class))->toBeInstanceOf(ListCommand::class);
});

it('prepends build mode middleware to the web group', function (): void {
    expect(app(Router::class)->getMiddlewareGroups()['web'][0])->toBe(BuildModeMiddleware::class);
});

it('compiles generated placeholder blade files during view cache', function (): void {
    app(BoneRepository::class)->putCompiled(
        'test.lazy-marked',
        '<section class="wirebones cached-wirebone"></section>',
    );

    $kernel = app(ConsoleKernel::class);

    if (method_exists($kernel, 'rerouteSymfonyCommandEvents')) {
        $kernel->rerouteSymfonyCommandEvents();
    }

    $this->artisan('view:cache')->assertSuccessful();

    $compiled = app(BoneRepository::class)->compiledPathFor('test.lazy-marked');

    expect(file_exists($compiled))->toBeTrue()
        ->and(app('blade.compiler')->isExpired($compiled))->toBeFalse();
});
