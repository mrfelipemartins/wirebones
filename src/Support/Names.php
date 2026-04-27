<?php

namespace MrFelipeMartins\Wirebones\Support;

use Illuminate\Support\Str;

final class Names
{
    /**
     * @param  list<string>  $prefixes
     */
    public static function defaultWireboneName(string $class, array $prefixes = []): string
    {
        $name = ltrim($class, '\\');

        foreach ($prefixes as $prefix) {
            if (str_starts_with($name, $prefix)) {
                $name = substr($name, strlen($prefix));
                break;
            }
        }

        return collect(explode('\\', $name))
            ->filter()
            ->map(fn (string $segment): string => Str::kebab($segment))
            ->implode('.');
    }

    public static function safeFileName(string $name): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name) ?: 'wirebone';

        return trim($safe, '._') ?: 'wirebone';
    }
}
