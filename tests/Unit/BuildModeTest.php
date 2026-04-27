<?php

use Illuminate\Http\Request;
use MrFelipeMartins\Wirebones\Support\BuildMode;

it('is inactive when the request is not bound', function (): void {
    app()->forgetInstance('request');

    expect(app(BuildMode::class)->active())->toBeFalse();
});

it('requires the configured build query parameter', function (): void {
    config()->set('wirebones.build_query', 'wirebones');

    expect(app(BuildMode::class)->active(Request::create('/dashboard')))->toBeFalse()
        ->and(app(BuildMode::class)->active(Request::create('/dashboard?wirebones=1')))->toBeTrue();
});

it('honors configured build tokens', function (): void {
    config()->set('wirebones.build_query', 'build');
    config()->set('wirebones.build_token_query', 'token');
    config()->set('wirebones.build_token', 'secret');

    $buildMode = app(BuildMode::class);

    expect($buildMode->active(Request::create('/dashboard?build=1&token=wrong')))->toBeFalse()
        ->and($buildMode->active(Request::create('/dashboard?build=1&token=secret')))->toBeTrue();
});

it('rejects tokenless build mode outside local and testing environments', function (): void {
    config()->set('wirebones.build_token', null);
    app()->detectEnvironment(fn (): string => 'production');

    expect(app(BuildMode::class)->active(Request::create('/dashboard?wirebones=1')))->toBeFalse();
});

