<?php

namespace MrFelipeMartins\Wirebones\Runtime;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use MrFelipeMartins\Wirebones\Support\BoneRepository;
use MrFelipeMartins\Wirebones\Support\WireboneDefinition;

final class CompiledPlaceholderGenerator
{
    public function __construct(
        private readonly BoneRepository $bones,
        private readonly Filesystem $files,
        private readonly SkeletonRenderer $renderer,
        private readonly BladeCompiler $compiler,
    ) {
    }

    public function prepareViewPath(): void
    {
        $this->files->ensureDirectoryExists($this->bones->compiledPath());
    }

    /**
     * @param  iterable<WireboneDefinition>  $definitions
     * @param  array<string, array<string, mixed>>  $captures
     */
    public function generateForDefinitions(iterable $definitions, array $captures): int
    {
        $count = 0;

        foreach ($definitions as $definition) {
            $wirebone = $captures[$definition->name] ?? null;

            if (! $wirebone) {
                continue;
            }

            $this->bones->putCompiled(
                $definition->name,
                $this->toStaticBlade($this->minifyHtml($this->renderer->render($definition, $wirebone))),
            );

            $count++;
        }

        return $count;
    }

    public function compileAll(): int
    {
        $count = 0;

        foreach ($this->bones->allCompiledFiles() as $path) {
            $this->compiler->compile($path);

            $compiled = $this->compiler->getCompiledPath($path);
            $sourceModified = $this->files->lastModified($path);

            if ($this->files->exists($compiled)) {
                touch($compiled, $sourceModified + 1);
            }

            $count++;
        }

        return $count;
    }

    private function toStaticBlade(string $html): string
    {
        return str_replace('@', '@@', $html);
    }

    private function minifyHtml(string $html): string
    {
        $html = preg_replace('/>\s+</', '><', $html) ?? $html;
        $html = preg_replace('/\s{2,}/', ' ', $html) ?? $html;

        return trim($html);
    }
}
