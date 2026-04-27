<?php

namespace MrFelipeMartins\Wirebones\Discovery;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Livewire\Component;
use ReflectionClass;
use MrFelipeMartins\Wirebones\Attributes\Wirebone;
use MrFelipeMartins\Wirebones\Support\Names;
use MrFelipeMartins\Wirebones\Support\WireboneDefinition;

final class WireboneDiscoverer
{
    public function __construct(
        private readonly Filesystem $files,
    ) {
    }

    /**
     * @return list<WireboneDefinition>
     */
    public function discover(): array
    {
        $definitions = [];

        foreach ($this->componentFiles() as $file) {
            $class = $this->classFromFile($file);

            if (! $class || ! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Component::class)) {
                continue;
            }

            $attributes = $reflection->getAttributes(Wirebone::class);

            if ($attributes === []) {
                continue;
            }

            /** @var Wirebone $attribute */
            $attribute = $attributes[0]->newInstance();

            $definitions[] = new WireboneDefinition(
                class: $class,
                name: $attribute->name ?: Names::defaultWireboneName(
                    $class,
                    $this->componentNamespacePrefixes(),
                ),
                route: $attribute->route,
                breakpoints: $this->normalizeBreakpoints($attribute->breakpoints),
                wait: max(0, $attribute->wait ?? Config::integer('wirebones.wait', 800)),
                file: $file,
                captureConfig: $this->captureConfig($attribute),
            );
        }

        return $definitions;
    }

    public function definitionForComponent(object|string $component): ?WireboneDefinition
    {
        $class = is_object($component) ? $component::class : ltrim($component, '\\');

        if (! class_exists($class)) {
            return null;
        }

        $reflection = new ReflectionClass($class);

        $attributes = $reflection->getAttributes(Wirebone::class);

        if ($attributes === []) {
            return null;
        }

        /** @var Wirebone $attribute */
        $attribute = $attributes[0]->newInstance();

        return new WireboneDefinition(
            class: $class,
            name: $attribute->name ?: Names::defaultWireboneName(
                $class,
                $this->componentNamespacePrefixes(),
            ),
            route: $attribute->route,
            breakpoints: $this->normalizeBreakpoints($attribute->breakpoints),
            wait: max(0, $attribute->wait ?? Config::integer('wirebones.wait', 800)),
            file: $reflection->getFileName() ?: null,
            captureConfig: $this->captureConfig($attribute),
        );
    }

    /**
     * @return list<string>
     */
    private function componentFiles(): array
    {
        $paths = $this->componentPaths();
        $files = [];

        foreach ($paths as $path) {
            if (! $this->files->isDirectory($path)) {
                continue;
            }

            foreach ($this->files->allFiles($path) as $file) {
                if ($file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        return array_values(array_unique($files));
    }

    /**
     * @return list<string>
     */
    private function componentPaths(): array
    {
        $path = Config::string('livewire.class_path', app_path('Livewire'));

        return $path === '' ? [] : [$path];
    }

    /**
     * @return list<string>
     */
    private function componentNamespacePrefixes(): array
    {
        $namespace = Config::string('livewire.class_namespace', 'App\\Livewire');

        return $namespace === '' ? [] : [trim($namespace, '\\').'\\'];
    }

    private function classFromFile(string $file): ?string
    {
        $tokens = token_get_all($this->files->get($file));
        $namespace = '';
        $class = null;

        for ($i = 0, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];

            if (! is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $namespace = $this->readNamespace($tokens, $i + 1);
                continue;
            }

            if ($token[0] === T_CLASS && $this->previousMeaningfulTokenIsNotNew($tokens, $i)) {
                $class = $this->readClassName($tokens, $i + 1);
                break;
            }
        }

        if (! $class) {
            return null;
        }

        return $namespace ? "{$namespace}\\{$class}" : $class;
    }

    /**
     * @param  list<mixed>  $tokens
     */
    private function readNamespace(array $tokens, int $offset): string
    {
        $namespace = '';

        for ($i = $offset, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];

            if ($token === ';' || $token === '{') {
                break;
            }

            if (
                is_array($token)
                && in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)
                && is_string($token[1])
            ) {
                $namespace .= $token[1];
            }
        }

        return trim($namespace, '\\');
    }

    /**
     * @param  list<mixed>  $tokens
     */
    private function readClassName(array $tokens, int $offset): ?string
    {
        for ($i = $offset, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];

            if (is_array($token) && $token[0] === T_STRING && is_string($token[1])) {
                return $token[1];
            }
        }

        return null;
    }

    /**
     * @param  list<mixed>  $tokens
     */
    private function previousMeaningfulTokenIsNotNew(array $tokens, int $index): bool
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            $token = $tokens[$i];

            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return ! (is_array($token) && $token[0] === T_NEW);
        }

        return true;
    }

    /**
     * @param  list<int>|null  $breakpoints
     * @return list<int>
     */
    private function normalizeBreakpoints(?array $breakpoints): array
    {
        $source = $breakpoints ?: Config::array('wirebones.breakpoints', [375, 768, 1280]);

        return array_values(collect($source)
            ->filter(fn ($width): bool => is_int($width) || is_numeric($width))
            ->map(fn ($width): int => is_int($width) ? $width : (int) $width)
            ->filter(fn (int $width): bool => $width > 0)
            ->unique()
            ->sort()
            ->values()
            ->all());
    }

    /**
     * @return array{leafTags: list<string>, excludeTags: list<string>, excludeSelectors: list<string>, captureRoundedBorders: bool}
     */
    private function captureConfig(Wirebone $attribute): array
    {
        return [
            'leafTags' => $this->normalizeStrings($attribute->leafTags ?: Config::array('wirebones.leaf_tags', [])),
            'excludeTags' => $this->normalizeStrings($attribute->excludeTags ?: Config::array('wirebones.exclude_tags', [])),
            'excludeSelectors' => $this->normalizeStrings($attribute->excludeSelectors ?: Config::array('wirebones.exclude_selectors', [])),
            'captureRoundedBorders' => $attribute->captureRoundedBorders ?? Config::boolean('wirebones.capture_rounded_borders', true),
        ];
    }

    /**
     * @param  array<mixed>  $values
     * @return list<string>
     */
    private function normalizeStrings(array $values): array
    {
        return array_values(collect($values)
            ->filter(fn ($value): bool => is_string($value) || is_numeric($value))
            ->map(fn ($value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all());
    }
}
