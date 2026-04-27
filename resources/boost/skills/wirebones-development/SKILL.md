---
name: wirebones-development
description: Build and maintain Wirebones skeleton placeholders for lazy Livewire components in Laravel applications.
---

# Wirebones Development

## When to Use This Skill

Use this skill when adding, changing, generating, or debugging Wirebones skeleton placeholders for lazy Livewire components.

Wirebones renders real Laravel pages in Chromium, captures the computed layout of marked lazy Livewire components, and replaces Livewire's lazy placeholder with generated skeleton Blade files.

## Core Rules

- Only add Wirebones to Livewire components that are actually lazy or deferred in the page where they render.
- Mark components explicitly with `#[Wirebone]`; Wirebones does not implicitly capture every lazy component.
- Always set `route` to a real page where the lazy component is rendered.
- Generated placeholder `.blade.php` files are build artifacts. Re-run `wirebones:build` after changing layout, captured views, breakpoints, wait time, colors, rendering config, or capture auth.
- If no generated placeholder exists for a marked component, Wirebones should fall back to Livewire's normal placeholder behavior.

## Basic Usage

```php
use MrFelipeMartins\Wirebones\Attributes\Wirebone;

#[Wirebone(route: '/dashboard')]
class Revenue extends \Livewire\Component
{
    //
}
```

Enable Livewire delayed loading using the syntax that matches the component's UX:

```blade
{{-- Loads when the component enters the viewport --}}
<livewire:revenue lazy />

{{-- Loads immediately after the initial page load --}}
<livewire:revenue defer />

{{-- Useful when many similar lazy components should share one request --}}
<livewire:revenue lazy.bundle />
```

You may also use Livewire's `#[Lazy]` or `#[Defer]` attributes on the component class when every usage of that component should be delayed by default. Keep `#[Wirebone]` separate: it controls Wirebones capture/generation, not Livewire's loading trigger.

## Attribute Options

Use explicit options when the generated name, capture route, breakpoints, wait time, or DOM capture behavior needs tuning:

```php
#[Wirebone(
    name: 'revenue-card',
    route: '/dashboard',
    breakpoints: [375, 768, 1280],
    wait: 800,
    leafTags: ['p', 'h1', 'h2'],
    excludeTags: ['svg'],
    excludeSelectors: ['[data-no-wirebone]'],
    captureRoundedBorders: true,
)]
```

- `name`: generated placeholder lookup name; defaults to the Livewire-style component name.
- `route`: page Wirebones visits to find and capture the component.
- `breakpoints`: viewport widths to capture.
- `wait`: milliseconds to wait after page load before capturing.
- `leafTags`: tags captured as content bones.
- `excludeTags`: tags ignored during capture.
- `excludeSelectors`: CSS selectors ignored during capture.
- `captureRoundedBorders`: whether border radius should be captured from rendered DOM.

## Commands

```bash
php artisan wirebones:list
php artisan wirebones:build
php artisan wirebones:build --component=revenue-card
php artisan wirebones:clear
```

Use `wirebones:list` before debugging capture problems. Use targeted `--component` builds during local iteration when only one component changed.

## Authenticated Routes

If the route redirects to login during capture, configure a build token and user:

```dotenv
WIREBONES_BUILD_TOKEN=secret-build-token
WIREBONES_AUTH_USER_ID=1
WIREBONES_AUTH_GUARD=web
```

For cookie, header, or storage-state based auth, use `config/wirebones.php` or the build command's `--cookie` and `--header` options.

## Vite Auto-Rebuilds

For local development, add the Vite plugin from the Composer package:

```js
import wirebones from './vendor/mrfelipemartins/wirebones/vite/index.js'
```

```js
export default defineConfig({
    plugins: [
        laravel({ input: ['resources/css/app.css', 'resources/js/app.js'], refresh: true }),
        wirebones(),
    ],
})
```

The Laravel app server must already be running. The plugin runs only during `vite serve`, watches changed PHP and Blade files, asks Laravel which `#[Wirebone]` component changed, and runs a targeted `wirebones:build --component=...`.

## Troubleshooting

- Stale skeleton: run `php artisan wirebones:build`.
- Color or rendering config changed: run `php artisan wirebones:build` because those values are baked into generated Blade files.
- Protected route redirects to login: configure build auth with `WIREBONES_BUILD_TOKEN`, `WIREBONES_AUTH_USER_ID`, and `WIREBONES_AUTH_GUARD`, or provide cookies/headers/storage state.
- Component is not captured: check `wirebones:list`, confirm the `route` is correct, confirm the component is rendered lazily on that route, and run with `--debug` if needed.
- Local edits are not rebuilding skeletons: enable the Vite plugin, run `vite serve`, and keep the Laravel app server available at the URL used by `wirebones:build`.
