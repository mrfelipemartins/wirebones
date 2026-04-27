<?php

namespace MrFelipeMartins\Wirebones\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Wirebone
{
    /**
     * @param  list<int>|null  $breakpoints
     * @param  list<string>|null  $leafTags
     * @param  list<string>|null  $excludeTags
     * @param  list<string>|null  $excludeSelectors
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $route = null,
        public readonly ?array $breakpoints = null,
        public readonly ?int $wait = null,
        public readonly ?array $leafTags = null,
        public readonly ?array $excludeTags = null,
        public readonly ?array $excludeSelectors = null,
        public readonly ?bool $captureRoundedBorders = null,
    ) {
    }
}
