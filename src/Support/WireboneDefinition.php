<?php

namespace MrFelipeMartins\Wirebones\Support;

use JsonSerializable;

final class WireboneDefinition implements JsonSerializable
{
    /**
     * @param  list<int>  $breakpoints
     * @param  array{leafTags: list<string>, excludeTags: list<string>, excludeSelectors: list<string>, captureRoundedBorders: bool}  $captureConfig
     */
    public function __construct(
        public readonly string $class,
        public readonly string $name,
        public readonly ?string $route,
        public readonly array $breakpoints,
        public readonly int $wait,
        public readonly ?string $file = null,
        public readonly array $captureConfig = [
            'leafTags' => [],
            'excludeTags' => [],
            'excludeSelectors' => [],
            'captureRoundedBorders' => true,
        ],
    ) {
    }

    /**
     * @return array{class: string, name: string, route: string|null, breakpoints: list<int>, wait: int, file: string|null, captureConfig: array<string, mixed>}
     */
    public function jsonSerialize(): array
    {
        return [
            'class' => $this->class,
            'name' => $this->name,
            'route' => $this->route,
            'breakpoints' => $this->breakpoints,
            'wait' => $this->wait,
            'file' => $this->file,
            'captureConfig' => $this->captureConfig,
        ];
    }
}
