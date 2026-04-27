<?php

namespace MrFelipeMartins\Wirebones;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use MrFelipeMartins\Wirebones\Commands\BuildCommand;
use MrFelipeMartins\Wirebones\Commands\ChangedCommand;
use MrFelipeMartins\Wirebones\Commands\ClearCommand;
use MrFelipeMartins\Wirebones\Commands\ListCommand;
use MrFelipeMartins\Wirebones\Runtime\BuildModeMiddleware;
use MrFelipeMartins\Wirebones\Runtime\CompiledPlaceholderGenerator;
use MrFelipeMartins\Wirebones\Runtime\LivewireIntegration;

final class WirebonesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/wirebones.php', 'wirebones');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'wirebones');
        $this->loadCompiledPlaceholderViews();

        $this->publishes([
            __DIR__.'/../config/wirebones.php' => config_path('wirebones.php'),
        ], 'wirebones-config');

        $this->app->make(Router::class)->prependMiddlewareToGroup('web', BuildModeMiddleware::class);

        $this->app->make(LivewireIntegration::class)->boot();

        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            if ($event->command !== 'view:cache') {
                return;
            }

            $this->loadCompiledPlaceholderViews();
        });

        Event::listen(CommandFinished::class, function (CommandFinished $event): void {
            if ($event->command !== 'view:cache' || $event->exitCode !== 0) {
                return;
            }

            $generator = $this->app->make(CompiledPlaceholderGenerator::class);

            $this->loadCompiledPlaceholderViews();
            $generator->compileAll();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                BuildCommand::class,
                ChangedCommand::class,
                ClearCommand::class,
                ListCommand::class,
            ]);
        }
    }

    private function loadCompiledPlaceholderViews(): void
    {
        $path = Config::string('wirebones.compiled_path', storage_path('framework/wirebones/views'));

        $this->app->make(Filesystem::class)->ensureDirectoryExists($path);
        $this->loadViewsFrom($path, 'wirebones.generated');
    }
}
