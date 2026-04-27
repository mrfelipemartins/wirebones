<?php

namespace MrFelipeMartins\Wirebones\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use RuntimeException;

final class BoneRepository
{
    public function __construct(
        private readonly Filesystem $files,
    ) {
    }

    public function outputPath(): string
    {
        return Config::string('wirebones.output_path', storage_path('framework/wirebones'));
    }

    public function compiledPath(): string
    {
        return Config::string('wirebones.compiled_path', storage_path('framework/wirebones/views'));
    }

    public function compiledPathFor(string $name): string
    {
        return $this->compiledPath().DIRECTORY_SEPARATOR.Names::safeFileName($name).'.blade.php';
    }

    public function putCompiled(string $name, string $contents): string
    {
        $path = $this->compiledPathFor($name);
        $this->ensureSafeDirectory($path, $this->compiledPath());

        $this->files->put($path, $contents);

        return $path;
    }
    /**
     * @return list<string>
     */
    public function allCompiledFiles(): array
    {
        $path = $this->compiledPath();

        if (! $this->files->isDirectory($path)) {
            return [];
        }

        return array_values(collect($this->files->files($path))
            ->filter(fn ($file): bool => str_ends_with($file->getFilename(), '.blade.php'))
            ->map(fn ($file): string => $file->getPathname())
            ->values()
            ->all());
    }

    public function clear(): int
    {
        $count = 0;

        foreach ($this->allCompiledFiles() as $file) {
            $this->files->delete($file);
            $count++;
        }

        return $count;
    }

    private function ensureSafeDirectory(string $path, string $root): void
    {
        $output = realpath($root) ?: $root;
        $directory = dirname($path);

        $this->files->ensureDirectoryExists($directory);

        $resolvedDirectory = realpath($directory);

        if ($resolvedDirectory === false || ! str_starts_with($resolvedDirectory, $output)) {
            throw new RuntimeException("Refusing to write outside the Wirebones output directory: {$path}");
        }
    }
}
