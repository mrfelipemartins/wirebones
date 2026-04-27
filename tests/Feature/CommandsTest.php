<?php

use Illuminate\Filesystem\Filesystem;
use MrFelipeMartins\Wirebones\Support\BoneRepository;

it('lists discovered wirebone components', function (): void {
    $this->artisan('wirebones:list')
        ->expectsOutputToContain('custom.card')
        ->expectsOutputToContain('marked-default-component')
        ->expectsOutputToContain('375, 768, 1280')
        ->assertSuccessful();
});

it('clears generated wirebone files', function (): void {
    app(BoneRepository::class)->putCompiled('first', '<div>first</div>');
    app(BoneRepository::class)->putCompiled('second', '<div>second</div>');

    $this->artisan('wirebones:clear')
        ->expectsOutput('Deleted 2 Wirebones files.')
        ->assertSuccessful();

    expect(app(BoneRepository::class)->allCompiledFiles())->toBe([]);
});

it('build succeeds without components', function (): void {
    $empty = $this->wirebonesOutputPath.'/empty-components';
    app(Filesystem::class)->ensureDirectoryExists($empty);
    config()->set('livewire.class_path', $empty);

    $this->artisan('wirebones:build', ['url' => 'http://example.test'])
        ->expectsOutputToContain('No #[Wirebone] components found to capture.')
        ->assertSuccessful();
});

it('build fails when selected components are missing routes', function (): void {
    $this->artisan('wirebones:build', [
        'url' => 'http://example.test',
        '--component' => ['marked-default-component'],
    ])
        ->expectsOutputToContain('Skipping marked-default-component: #[Wirebone] is missing a route.')
        ->assertFailed();
});

it('build passes expected payload to node and removes its temporary payload file', function (): void {
    $payloadCopy = $this->wirebonesOutputPath.'/payload-copy.json';

    config()->set('wirebones.auth.cookies', [
        ['name' => 'configured', 'value' => 'cookie', 'domain' => 'example.test', 'path' => '/'],
    ]);
    config()->set('wirebones.auth.headers', [
        'Authorization' => 'Bearer configured',
        'Host' => 'ignored',
    ]);
    config()->set('wirebones.auth.storage_state', $this->wirebonesOutputPath.'/state.json');

    $this->useFakeNode(
        '$payloadPath = $argv[2];'."\n".
        '$payload = json_decode(file_get_contents($payloadPath), true);'."\n".
        'copy($payloadPath, '.var_export($payloadCopy, true).');'."\n".
        'file_put_contents($payload["capturePath"], "{}");'."\n".
        'echo "fake capture ok\n";'."\n".
        'exit(0);'."\n",
    );

    $this->artisan('wirebones:build', [
        'url' => 'http://example.test/base/',
        '--component' => ['test.lazy-marked'],
        '--cookie' => ['cli=value'],
        '--header' => ['X-Wirebones: build'],
        '--headed' => true,
        '--debug' => true,
    ])
        ->expectsOutputToContain('Capturing Wirebones skeletons...')
        ->expectsOutputToContain('fake capture ok')
        ->expectsOutputToContain('Generated 0 compiled Wirebones placeholders.')
        ->assertSuccessful();

    $payload = json_decode(file_get_contents($payloadCopy), true);

    expect($payload)->toMatchArray([
        'baseUrl' => 'http://example.test/base',
        'outputPath' => $this->wirebonesOutputPath,
        'capturePath' => $this->wirebonesOutputPath.'/build-captures.tmp',
        'query' => 'wirebones',
        'tokenQuery' => 'wirebones_token',
        'viewportHeight' => 900,
        'headed' => true,
        'debug' => true,
        'auth' => [
            'cookies' => [
                ['name' => 'configured', 'value' => 'cookie', 'domain' => 'example.test', 'path' => '/'],
                ['name' => 'cli', 'value' => 'value', 'domain' => 'example.test', 'path' => '/'],
            ],
            'headers' => [
                'Authorization' => 'Bearer configured',
                'X-Wirebones' => 'build',
            ],
            'storageState' => $this->wirebonesOutputPath.'/state.json',
        ],
    ])
        ->and($payload['definitions'])->toHaveCount(1)
        ->and($payload['definitions'][0])->toMatchArray([
            'name' => 'test.lazy-marked',
            'class' => 'MrFelipeMartins\Wirebones\\Tests\\Fixtures\\Livewire\\LazyMarkedComponent',
            'route' => '/wirebones-test',
            'breakpoints' => [375, 768, 1280],
            'wait' => 25,
            'captureConfig' => [
                'leafTags' => ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'td', 'th'],
                'excludeTags' => [],
                'excludeSelectors' => [],
                'captureRoundedBorders' => true,
            ],
        ])
        ->and(file_exists(storage_path('framework/wirebones/build-input.json')))->toBeFalse()
        ->and(file_exists($this->wirebonesOutputPath.'/build-captures.tmp'))->toBeFalse();
});

it('build reports capture script failures', function (): void {
    $this->useFakeNode(
        'fwrite(STDERR, "fake capture failed\n");'."\n".
        'exit(7);'."\n",
    );

    $this->artisan('wirebones:build', [
        'url' => 'http://example.test',
        '--component' => ['test.lazy-marked'],
    ])
        ->expectsOutputToContain('Capturing Wirebones skeletons...')
        ->expectsOutputToContain('fake capture failed')
        ->expectsOutputToContain('Wirebones capture failed.')
        ->assertExitCode(7);
});

it('build generates compiled blade placeholders', function (): void {
    $this->useFakeNode(
        '$payload = json_decode(file_get_contents($argv[2]), true);'."\n".
        '$wirebone = ['."\n".
        '    "name" => "test.lazy-marked",'."\n".
        '    "class" => "MrFelipeMartins\\\\Wirebones\\\\Tests\\\\Fixtures\\\\Livewire\\\\LazyMarkedComponent",'."\n".
        '    "route" => "/wirebones-test",'."\n".
        '    "rootTag" => "section",'."\n".
        '    "breakpoints" => ["375" => ["height" => 48, "bones" => [[0, 0, 100, 48, 8]]]],'."\n".
        '];'."\n".
        'file_put_contents($payload["capturePath"], json_encode(["test.lazy-marked" => $wirebone]));'."\n".
        'exit(0);'."\n",
    );

    $this->artisan('wirebones:build', [
        'url' => 'http://example.test',
        '--component' => ['test.lazy-marked'],
    ])
        ->expectsOutputToContain('Generated 1 compiled Wirebones placeholder.')
        ->assertSuccessful();

    $compiled = app(BoneRepository::class)->compiledPathFor('test.lazy-marked');
    $contents = file_get_contents($compiled);

    expect(file_exists($compiled))->toBeTrue()
        ->and($contents)->toContain('@@media')
        ->and($contents)->not->toContain("\n")
        ->and($contents)->not->toContain('  ')
        ->and(glob($this->wirebonesOutputPath.'/*.json'))->toBe([]);
});
