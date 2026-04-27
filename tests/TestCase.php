<?php

namespace MrFelipeMartins\Wirebones\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Features\SupportLazyLoading\SupportLazyLoading;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use MrFelipeMartins\Wirebones\Tests\Fixtures\Livewire\LazyMarkedComponent;
use MrFelipeMartins\Wirebones\Tests\Fixtures\Livewire\LazyUnmarkedComponent;
use MrFelipeMartins\Wirebones\WirebonesServiceProvider;

abstract class TestCase extends Orchestra
{
    protected string $wirebonesOutputPath;

    private string $previousPath = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->wirebonesOutputPath = sys_get_temp_dir().'/wirebones-tests/'.str_replace('.', '', uniqid('', true));
        (new Filesystem())->ensureDirectoryExists($this->wirebonesOutputPath);
        $this->wirebonesOutputPath = (string) realpath($this->wirebonesOutputPath);

        config()->set('wirebones.output_path', $this->wirebonesOutputPath);
        config()->set('wirebones.compiled_path', $this->wirebonesOutputPath.'/views');
        config()->set('cache.default', 'array');
        config()->set('cache.stores.array', ['driver' => 'array']);

        SupportLazyLoading::$disableWhileTesting = false;

        Livewire::component('wirebones-test-marked', LazyMarkedComponent::class);
        Livewire::component('wirebones-test-unmarked', LazyUnmarkedComponent::class);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->wirebonesOutputPath ?? '');

        if ($this->previousPath !== '') {
            putenv('PATH='.$this->previousPath);
            $_SERVER['PATH'] = $this->previousPath;
            $_ENV['PATH'] = $this->previousPath;
        }

        parent::tearDown();
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            WirebonesServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:YWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWE=');
        $app['config']->set('app.url', 'http://localhost');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('cache.stores.array', ['driver' => 'array']);
        $app['config']->set('view.paths', [__DIR__.'/Fixtures/views', resource_path('views')]);
        $app['config']->set('livewire.class_namespace', 'MrFelipeMartins\Wirebones\\Tests\\Fixtures\\Livewire');
        $app['config']->set('livewire.class_path', __DIR__.'/Fixtures/Livewire');
    }

    protected function defineRoutes($router): void
    {
        Route::middleware('web')->get('/wirebones-test', fn (): string => Blade::render(<<<'HTML'
            <!doctype html>
            <html>
                <head>
                    <title>Wirebones Test</title>
                    @livewireStyles
                </head>
                <body>
                    <livewire:wirebones-test-marked lazy />
                    <livewire:wirebones-test-unmarked lazy />
                    @livewireScripts
                </body>
            </html>
        HTML));
    }

    protected function useFakeNode(string $scriptBody): string
    {
        $bin = $this->wirebonesOutputPath.'/bin';
        (new Filesystem())->ensureDirectoryExists($bin);

        $node = $bin.'/node';
        file_put_contents($node, "#!/usr/bin/env php\n<?php\n".$scriptBody);
        chmod($node, 0755);

        $this->previousPath = getenv('PATH') ?: '';
        $path = $bin.PATH_SEPARATOR.$this->previousPath;
        putenv('PATH='.$path);
        $_SERVER['PATH'] = $path;
        $_ENV['PATH'] = $path;

        return $node;
    }
}
