<?php

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MrFelipeMartins\Wirebones\Runtime\BuildModeMiddleware;

it('authenticates the configured user while capture build mode is active', function (): void {
    Auth::provider('wirebones-test', fn (): UserProvider => new class implements UserProvider
    {
        public function retrieveById($identifier): ?Authenticatable
        {
            return new GenericUser(['id' => $identifier]);
        }

        public function retrieveByToken($identifier, $token): ?Authenticatable
        {
            return null;
        }

        public function updateRememberToken(Authenticatable $user, $token): void
        {
        }

        public function retrieveByCredentials(array $credentials): ?Authenticatable
        {
            return null;
        }

        public function validateCredentials(Authenticatable $user, array $credentials): bool
        {
            return false;
        }

        public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
        {
        }
    });

    config()->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'wirebones']);
    config()->set('auth.providers.wirebones', ['driver' => 'wirebones-test']);
    config()->set('wirebones.auth.guard', 'admin');
    config()->set('wirebones.auth.user_id', '42');

    $request = Request::create('/dashboard?wirebones=1');

    app(BuildModeMiddleware::class)->handle($request, fn (): string => 'ok');

    expect(Auth::guard('admin')->id())->toBe('42');
});

it('does not authenticate when capture build mode is inactive', function (): void {
    config()->set('wirebones.auth.user_id', 42);

    $request = Request::create('/dashboard');

    expect(app(BuildModeMiddleware::class)->handle($request, fn (): string => 'ok'))->toBe('ok');
});
