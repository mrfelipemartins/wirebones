<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Generated Skeleton Output
    |--------------------------------------------------------------------------
    |
    | Wirebones writes generated placeholder Blade files under this directory.
    | The default keeps artifacts out of source control and lets deployments
    | rebuild or cache them.
    |
    */

    'output_path' => storage_path('framework/wirebones'),

    /*
    |--------------------------------------------------------------------------
    | Compiled Placeholder Views
    |--------------------------------------------------------------------------
    |
    | Wirebones writes static .blade.php placeholder views during
    | wirebones:build and around Laravel's view:cache command so runtime
    | rendering stays cheap.
    |
    */

    'compiled_path' => storage_path('framework/wirebones/views'),

    /*
    |--------------------------------------------------------------------------
    | Capture Defaults
    |--------------------------------------------------------------------------
    */

    'breakpoints' => [375, 768, 1280],

    'wait' => 800,

    'viewport_height' => 900,

    /*
    |--------------------------------------------------------------------------
    | Capture Fidelity
    |--------------------------------------------------------------------------
    |
    | These options tune how Wirebones turns rendered DOM into skeleton shapes.
    | Leaf tags are captured as content bones even when they have inline
    | children, while excluded tags and selectors are ignored during capture.
    | Table structures are handled separately so cells do not become solid
    | full-width blocks.
    |
    */

    'leaf_tags' => ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li'],

    'exclude_tags' => [],

    'exclude_selectors' => [],

    'capture_rounded_borders' => true,

    /*
    |--------------------------------------------------------------------------
    | Build Mode
    |--------------------------------------------------------------------------
    |
    | During capture, Wirebones appends these query parameters to visited routes.
    | If build_token is null, build mode is allowed only when the app environment
    | is local or testing.
    |
    */

    'build_query' => 'wirebones',

    'build_token_query' => 'wirebones_token',

    'build_token' => env('WIREBONES_BUILD_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Capture Authentication
    |--------------------------------------------------------------------------
    |
    | Protected routes can be captured by temporarily authenticating the Laravel
    | request while build mode is active, or by passing browser auth state to
    | Playwright for cookie/header based applications.
    |
    */

    'auth' => [
        'guard' => env('WIREBONES_AUTH_GUARD', 'web'),

        'user_id' => env('WIREBONES_AUTH_USER_ID'),

        'cookies' => [],

        'headers' => [],

        'storage_state' => env('WIREBONES_STORAGE_STATE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Generated Skeleton Rendering
    |--------------------------------------------------------------------------
    |
    | These values are baked into the generated placeholder Blade files during
    | wirebones:build. Re-run the build command after changing them.
    */

    'color' => 'rgb(161 161 170 / 0.22)',

    'container_color' => 'rgb(161 161 170 / 0.12)',

    'dark_color' => 'rgb(161 161 170 / 0.24)',

    'dark_container_color' => 'rgb(161 161 170 / 0.14)',

    'shimmer_color' => 'rgb(255 255 255 / 0.50)',

    'dark_shimmer_color' => 'rgb(24 24 27 / 0.50)',

    'animation' => 'pulse', // pulse, shimmer, solid

    'speed' => null,

    'shimmer_angle' => 110,

    'stagger' => false,

    'transition' => false,

    'render_containers' => true,

    /*
    |--------------------------------------------------------------------------
    | Responsive Selection
    |--------------------------------------------------------------------------
    |
    | Most Blade/Tailwind layouts respond to viewport media queries, so viewport
    | matching is the default. Use "container" only for components whose layout
    | is driven by container queries or intrinsic container width.
    |
    */

    'responsive_strategy' => 'viewport', // viewport, container
];
