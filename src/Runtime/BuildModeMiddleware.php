<?php

namespace MrFelipeMartins\Wirebones\Runtime;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use MrFelipeMartins\Wirebones\Support\BuildMode;

final class BuildModeMiddleware
{
    public function __construct(
        private readonly BuildMode $buildMode,
    ) {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->buildMode->active($request)) {
            $this->authenticateForCapture();

            if (class_exists(\Livewire\Livewire::class)) {
                \Livewire\Livewire::withoutLazyLoading();
            }
        }

        return $next($request);
    }

    private function authenticateForCapture(): void
    {
        $userId = Config::get('wirebones.auth.user_id');

        if (! is_int($userId) && ! is_string($userId)) {
            return;
        }

        if ((string) $userId === '') {
            return;
        }

        $guard = Config::string('wirebones.auth.guard', 'web');

        Auth::guard($guard)->onceUsingId($userId);
    }
}
