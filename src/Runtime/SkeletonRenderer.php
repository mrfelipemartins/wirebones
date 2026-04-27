<?php

namespace MrFelipeMartins\Wirebones\Runtime;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use MrFelipeMartins\Wirebones\Support\BoneRepository;
use MrFelipeMartins\Wirebones\Support\WireboneDefinition;

final class SkeletonRenderer
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly Filesystem $files,
        private readonly BoneRepository $bones,
    ) {
    }

    /**
     * @param  array<string, mixed>  $wirebone
     */
    public function render(WireboneDefinition $definition, array $wirebone): string
    {
        $responsiveStrategy = Config::string('wirebones.responsive_strategy', 'viewport');
        $speed = Config::get('wirebones.speed');

        return $this->views->make('wirebones::placeholder', [
            'definition' => $definition,
            'wirebone' => $wirebone,
            'uid' => 'wb-'.substr(sha1($definition->class.'|'.$definition->name), 0, 10),
            'config' => [
                'color' => Config::string('wirebones.color', '#f0f0f0'),
                'containerColor' => Config::string('wirebones.container_color', '#f3f4f6'),
                'darkColor' => Config::string('wirebones.dark_color', 'oklch(37% 0.013 285.805)'),
                'darkContainerColor' => Config::string('wirebones.dark_container_color', 'oklch(27.4% 0.006 286.033)'),
                'shimmerColor' => Config::string('wirebones.shimmer_color', '#f7f7f7'),
                'darkShimmerColor' => Config::string('wirebones.dark_shimmer_color', 'oklch(44.2% 0.017 285.786)'),
                'animation' => Config::string('wirebones.animation', 'pulse'),
                'speed' => is_string($speed) ? $speed : null,
                'shimmerAngle' => Config::integer('wirebones.shimmer_angle', 110),
                'stagger' => config('wirebones.stagger', false),
                'renderContainers' => Config::boolean('wirebones.render_containers', true),
                'responsiveStrategy' => in_array($responsiveStrategy, ['viewport', 'container'], true)
                    ? $responsiveStrategy
                    : 'viewport',
            ],
        ])->render();
    }

    public function renderCompiled(WireboneDefinition $definition): ?string
    {
        $path = $this->bones->compiledPathFor($definition->name);

        if (! $this->files->exists($path)) {
            return null;
        }

        return $this->views->file($path)->render();
    }
}
