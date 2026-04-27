<?php

use Illuminate\Filesystem\Filesystem;
use MrFelipeMartins\Wirebones\Support\BoneRepository;

it('writes compiled blade placeholders inside the configured output path using safe filenames', function (): void {
    $repository = app(BoneRepository::class);

    $path = $repository->putCompiled('../unsafe/component', '<div>safe</div>');

    expect($path)->toEndWith('unsafe_component.blade.php')
        ->and($path)->toStartWith($this->wirebonesOutputPath.'/views')
        ->and(file_get_contents($path))->toContain('<div>safe</div>');
});

it('lists and clears only generated blade placeholder files', function (): void {
    $repository = app(BoneRepository::class);
    $files = app(Filesystem::class);

    $repository->putCompiled('one', '<div>one</div>');
    $repository->putCompiled('two', '<div>two</div>');
    $files->put($this->wirebonesOutputPath.'/keep.txt', 'keep');

    expect($repository->allCompiledFiles())->toHaveCount(2)
        ->and($repository->clear())->toBe(2)
        ->and($repository->allCompiledFiles())->toBe([])
        ->and($files->exists($this->wirebonesOutputPath.'/keep.txt'))->toBeTrue();
});
