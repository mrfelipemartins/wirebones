<?php

namespace MrFelipeMartins\Wirebones\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use MrFelipeMartins\Wirebones\Discovery\WireboneDiscoverer;

final class WireboneChangeDetector
{
    public function __construct(
        private readonly WireboneDiscoverer $discoverer,
        private readonly Filesystem $files,
    ) {
    }

    /**
     * @return list<string>
     */
    public function affectedComponents(string $path): array
    {
        $changedPath = $this->normalizePath($path);
        $affected = [];

        foreach ($this->discoverer->discover() as $definition) {
            if ($definition->file && $this->normalizePath($definition->file) === $changedPath) {
                $affected[] = $definition->name;

                continue;
            }

            if (! str_ends_with($changedPath, '.blade.php')) {
                continue;
            }

            foreach ($this->viewPathsFor($definition->class, $definition->file) as $viewPath) {
                if ($this->normalizePath($viewPath) === $changedPath) {
                    $affected[] = $definition->name;

                    break;
                }
            }
        }

        return array_values(array_unique($affected));
    }

    /**
     * @return list<string>
     */
    private function viewPathsFor(string $class, ?string $file): array
    {
        $views = $this->staticViewNames($file);
        $views[] = 'livewire.'.Names::defaultWireboneName($class, $this->componentNamespacePrefixes());

        $paths = [];

        foreach (array_unique(array_filter($views)) as $view) {
            foreach ($this->pathsForView($view) as $path) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * @return list<string>
     */
    private function staticViewNames(?string $file): array
    {
        if (! $file || ! $this->files->exists($file)) {
            return [];
        }

        preg_match_all('/\bview\s*\(\s*([\'"])([^\'"]+)\1/', $this->files->get($file), $matches);

        return array_values(array_filter($matches[2], 'is_string'));
    }

    /**
     * @return list<string>
     */
    private function pathsForView(string $view): array
    {
        $relative = str_replace('.', DIRECTORY_SEPARATOR, $view).'.blade.php';

        $paths = [];

        foreach (Config::array('view.paths', [resource_path('views')]) as $path) {
            if (is_string($path) && $path !== '') {
                $paths[] = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$relative;
            }
        }

        return $paths;
    }

    /**
     * @return list<string>
     */
    private function componentNamespacePrefixes(): array
    {
        $namespace = Config::string('livewire.class_namespace', 'App\\Livewire');

        return $namespace === '' ? [] : [trim($namespace, '\\').'\\'];
    }

    private function normalizePath(string $path): string
    {
        $real = realpath($path);

        if (is_string($real)) {
            return str_replace('\\', '/', $real);
        }

        if (! str_starts_with($path, DIRECTORY_SEPARATOR)) {
            $path = base_path($path);
        }

        return str_replace('\\', '/', $path);
    }
}
