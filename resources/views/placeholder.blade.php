@php
    $breakpoints = collect($wirebone['breakpoints'] ?? [])
        ->mapWithKeys(fn ($result, $width) => [(int) $width => $result])
        ->sortKeys();

    $first = $breakpoints->first() ?? [];
    $tag = preg_match('/^[a-z][a-z0-9-]*$/i', (string) ($wirebone['rootTag'] ?? $first['rootTag'] ?? 'div'))
        ? strtolower((string) ($wirebone['rootTag'] ?? $first['rootTag'] ?? 'div'))
        : 'div';

    $animation = in_array($config['animation'], ['pulse', 'shimmer', 'solid'], true) ? $config['animation'] : 'pulse';
    $speed = $config['speed'] ?: ($animation === 'shimmer' ? '2s' : '1.8s');
    $stagger = $config['stagger'] === true ? 80 : ((is_numeric($config['stagger'] ?? null) && $config['stagger'] > 0) ? (int) $config['stagger'] : 0);
    $responsiveStrategy = $config['responsiveStrategy'] === 'container' ? 'container' : 'viewport';
@endphp

<{!! $tag !!} class="wirebones {{ $uid }}" aria-hidden="true">
    <style>
        .{{ $uid }}{position:relative;width:100%}
        .{{ $uid }} .wirebones-layer{display:none;position:relative;width:100%;overflow:hidden}
        .{{ $uid }} .wirebones-bone{position:absolute;background:{{ $config['color'] }}}
        .dark .{{ $uid }} .wirebones-bone,.{{ $uid }}.dark .wirebones-bone{background:{{ $config['darkColor'] }}}
        @if ($animation === 'pulse')
        .{{ $uid }} .wirebones-bone{animation:{{ $uid }}-pulse {{ $speed }} ease-in-out infinite}
        @keyframes {{ $uid }}-pulse{0%,100%{opacity:1}50%{opacity:.58}}
        @elseif ($animation === 'shimmer')
        .{{ $uid }} .wirebones-bone{background-image:linear-gradient({{ $config['shimmerAngle'] }}deg,{{ $config['color'] }} 30%,{{ $config['shimmerColor'] }} 50%,{{ $config['color'] }} 70%);background-size:200% 100%;animation:{{ $uid }}-shimmer {{ $speed }} linear infinite}
        .dark .{{ $uid }} .wirebones-bone,.{{ $uid }}.dark .wirebones-bone{background-image:linear-gradient({{ $config['shimmerAngle'] }}deg,{{ $config['darkColor'] }} 30%,{{ $config['darkShimmerColor'] }} 50%,{{ $config['darkColor'] }} 70%)}
        @keyframes {{ $uid }}-shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
        @endif
        @if ($stagger > 0)
        .{{ $uid }} .wirebones-bone{opacity:0;animation-delay:var(--wirebones-delay,0ms)}
        @keyframes {{ $uid }}-show{from{opacity:0}to{opacity:1}}
        @endif
        @if ($responsiveStrategy === 'container')
        .{{ $uid }}{container-type:inline-size}
        @endif
        .{{ $uid }} .wirebones-container{background:{{ $config['containerColor'] }}}
        .dark .{{ $uid }} .wirebones-container,.{{ $uid }}.dark .wirebones-container{background:{{ $config['darkContainerColor'] }}}
        @foreach ($breakpoints->keys()->values() as $index => $width)
            @php
                $next = $breakpoints->keys()->values()->get($index + 1);
                $query = $next ? "(max-width: ".($next - 0.02)."px)" : "(min-width: 0px)";
                if ($index > 0 && $next) {
                    $query = "(min-width: {$width}px) and (max-width: ".($next - 0.02)."px)";
                } elseif ($index > 0) {
                    $query = "(min-width: {$width}px)";
                }
            @endphp
        @if ($responsiveStrategy === 'container')
        @supports not (container-type:inline-size){@media {{ $query }}{.{{ $uid }} .wirebones-layer-{{ $width }}{display:block}}}
        @supports (container-type:inline-size){@container {{ $query }}{.{{ $uid }} .wirebones-layer-{{ $width }}{display:block}}}
        @else
        @media {{ $query }}{.{{ $uid }} .wirebones-layer-{{ $width }}{display:block}}
        @endif
        @endforeach
    </style>

    @foreach ($breakpoints as $width => $result)
        <div class="wirebones-layer wirebones-layer-{{ $width }}" style="height:{{ (float) ($result['height'] ?? 0) }}px">
            @foreach (($result['bones'] ?? []) as $i => $bone)
                @php
                    $x = (float) ($bone[0] ?? 0);
                    $y = (float) ($bone[1] ?? 0);
                    $w = (float) ($bone[2] ?? 0);
                    $h = (float) ($bone[3] ?? 0);
                    $r = $bone[4] ?? 8;
                    $isContainer = (bool) ($bone[5] ?? false);
                    $radius = is_string($r) ? $r : ((float) $r).'px';
                    if (! preg_match('/^(50%|\d+(\.\d+)?px|\d+(\.\d+)?px \d+(\.\d+)?px \d+(\.\d+)?px \d+(\.\d+)?px)$/', $radius)) {
                        $radius = '8px';
                    }
                    $style = "left:{$x}%;top:{$y}px;width:{$w}%;height:{$h}px;border-radius:{$radius};";
                    if ($stagger > 0) {
                        $style .= "--wirebones-delay:".($i * $stagger)."ms;animation:{$uid}-show .3s ease-out ".($i * $stagger)."ms forwards";
                        if ($animation !== 'solid') {
                            $style .= ", {$uid}-{$animation} {$speed} ".($animation === 'shimmer' ? 'linear' : 'ease-in-out').' infinite';
                        }
                        $style .= ';';
                    }
                @endphp
                @continue($isContainer && ! $config['renderContainers'])
                <div
                    class="wirebones-bone @if($isContainer) wirebones-container @endif"
                    style="{{ $style }}"
                ></div>
            @endforeach
        </div>
    @endforeach
</{!! $tag !!}>
