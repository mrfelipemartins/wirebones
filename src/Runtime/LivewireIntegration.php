<?php

namespace MrFelipeMartins\Wirebones\Runtime;

use Livewire\Drawer\Utils;
use MrFelipeMartins\Wirebones\Discovery\WireboneDiscoverer;
use MrFelipeMartins\Wirebones\Support\BuildMode;

use function Livewire\on;

final class LivewireIntegration
{
    public function __construct(
        private readonly BuildMode $buildMode,
        private readonly WireboneDiscoverer $discoverer,
        private readonly SkeletonRenderer $renderer,
    ) {
    }

    public function boot(): void
    {
        if (! function_exists('Livewire\\on')) {
            return;
        }

        $this->bootBuildMode();
        $this->bootPlaceholderReplacement();
    }

    private function bootBuildMode(): void
    {
        on('render', function ($component) {
            if (! $this->buildMode->active()) {
                return null;
            }

            $definition = $this->discoverer->definitionForComponent($component);

            if (! $definition) {
                return null;
            }

            return function (string $html, callable $replace) use ($definition): void {
                $replace(Utils::insertAttributesIntoHtmlRoot($html, [
                    'data-wirebone' => $definition->name,
                    'data-wirebone-class' => $definition->class,
                ]));
            };
        });
    }

    private function bootPlaceholderReplacement(): void
    {
        on('render.placeholder', function ($component) {
            $definition = $this->discoverer->definitionForComponent($component);

            if (! $definition) {
                return null;
            }

            $compiled = $this->renderer->renderCompiled($definition);

            if (! $compiled) {
                return null;
            }

            return function (string $html, callable $replace) use ($compiled): void {
                $replace(Utils::insertAttributesIntoHtmlRoot(
                    $compiled,
                    $this->lazyLoadAttributesFrom($html),
                ));
            };
        });
    }

    /**
     * Livewire injects x-intersect/x-init into the placeholder root before
     * render.placeholder finishers run. Preserve it when replacing the root.
     *
     * @return array<string, string>
     */
    private function lazyLoadAttributesFrom(string $html): array
    {
        $attributes = [];

        foreach (['x-intersect', 'x-intersect.once', 'x-init'] as $name) {
            if (preg_match('/\s'.preg_quote($name, '/').'=("|\')(.*?)\1/s', $html, $match)) {
                $attributes[$name] = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        return $attributes;
    }
}
