<?php

namespace MrFelipeMartins\Wirebones\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

final class BuildMode
{
    public function active(?Request $request = null): bool
    {
        if (! app()->bound('request')) {
            return false;
        }

        $request ??= request();
        $query = Config::string('wirebones.build_query', 'wirebones');

        if (! $request->query->has($query)) {
            return false;
        }

        $token = config('wirebones.build_token');

        if (is_string($token) && $token !== '') {
            $tokenQuery = Config::string('wirebones.build_token_query', 'wirebones_token');

            return hash_equals($token, (string) $request->query($tokenQuery, ''));
        }

        return app()->environment(['local', 'testing']);
    }
}
