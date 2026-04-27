<?php

namespace MrFelipeMartins\Wirebones\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Process\Process;
use MrFelipeMartins\Wirebones\Discovery\WireboneDiscoverer;
use MrFelipeMartins\Wirebones\Runtime\CompiledPlaceholderGenerator;

final class BuildCommand extends Command
{
    protected $signature = 'wirebones:build
        {url? : Base URL for the running Laravel app}
        {--component=* : Capture only the given Wirebone name}
        {--cookie=* : Browser cookie for protected routes, in name=value format}
        {--header=* : Browser header for protected routes, in "Name: value" format}
        {--headed : Show the browser while capturing}
        {--debug : Print Playwright diagnostics}';

    protected $description = 'Capture generated skeletons for #[Wirebone] Livewire components.';

    public function handle(WireboneDiscoverer $discoverer, Filesystem $files, CompiledPlaceholderGenerator $compiled): int
    {
        $definitions = collect($discoverer->discover());
        $only = collect((array) $this->option('component'))->filter()->values();

        if ($only->isNotEmpty()) {
            $definitions = $definitions->filter(fn ($definition): bool => $only->contains($definition->name))->values();
        }

        if ($definitions->isEmpty()) {
            $this->warn('No #[Wirebone] components found to capture.');

            return self::SUCCESS;
        }

        $missingRoutes = $definitions->filter(fn ($definition): bool => ! $definition->route);

        if ($missingRoutes->isNotEmpty()) {
            foreach ($missingRoutes as $definition) {
                $this->warn("Skipping {$definition->name}: #[Wirebone] is missing a route.");
            }

            $definitions = $definitions->reject(fn ($definition): bool => ! $definition->route)->values();
        }

        if ($definitions->isEmpty()) {
            return self::FAILURE;
        }

        $url = $this->argument('url');
        $baseUrl = is_string($url) && $url !== '' ? $url : Config::string('app.url', 'http://localhost:8000');
        $token = Config::get('wirebones.build_token');
        $auth = $this->captureAuth($baseUrl);
        $outputPath = Config::string('wirebones.output_path', storage_path('framework/wirebones'));
        $capturePath = $outputPath.DIRECTORY_SEPARATOR.'build-captures.tmp';
        $input = [
            'baseUrl' => rtrim($baseUrl, '/'),
            'outputPath' => $outputPath,
            'capturePath' => $capturePath,
            'query' => Config::string('wirebones.build_query', 'wirebones'),
            'tokenQuery' => Config::string('wirebones.build_token_query', 'wirebones_token'),
            'token' => is_string($token) ? $token : null,
            'auth' => $auth,
            'viewportHeight' => Config::integer('wirebones.viewport_height', 900),
            'headed' => (bool) $this->option('headed'),
            'debug' => (bool) $this->option('debug'),
            'definitions' => $definitions->values()->all(),
        ];

        $files->ensureDirectoryExists(storage_path('framework/wirebones'));
        $payloadPath = storage_path('framework/wirebones/build-input.json');
        $contents = json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (! is_string($contents)) {
            $this->error('Wirebones failed to encode the capture payload.');

            return self::FAILURE;
        }

        $files->put($payloadPath, $contents);

        $script = realpath(__DIR__.'/../../bin/wirebones-capture.mjs');

        if (! $script) {
            $this->error('Wirebones capture script is missing.');

            return self::FAILURE;
        }

        $process = new Process(['node', $script, $payloadPath], base_path(), null, null, null);
        $process->setTty(false);

        $this->line('Capturing Wirebones skeletons...');

        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        $files->delete($payloadPath);

        if (! $process->isSuccessful()) {
            $files->delete($capturePath);
            $this->error('Wirebones capture failed.');

            return $process->getExitCode() ?: self::FAILURE;
        }

        $captures = $this->readCaptures($files, $capturePath);
        $files->delete($capturePath);

        if ($captures === null) {
            $this->error('Wirebones capture did not produce a valid build result.');

            return self::FAILURE;
        }

        $compiled->prepareViewPath();
        $count = $compiled->generateForDefinitions($definitions, $captures);
        $this->info("Generated {$count} compiled Wirebones placeholder".($count === 1 ? '' : 's').'.');

        return self::SUCCESS;
    }

    /**
     * @return array{cookies: list<array<string, mixed>>, headers: array<string, string>, storageState: string|null}
     */
    private function captureAuth(string $baseUrl): array
    {
        $configured = Config::get('wirebones.auth');
        $auth = is_array($configured) ? $configured : [];

        $cookies = $this->configuredCookies($auth['cookies'] ?? []);
        $headers = $this->configuredHeaders($auth['headers'] ?? []);

        foreach ((array) $this->option('cookie') as $cookie) {
            if (! is_string($cookie) || ! str_contains($cookie, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $cookie, 2);
            $name = trim($name);

            if ($name === '') {
                continue;
            }

            $cookies[] = [
                'name' => $name,
                'value' => $value,
                'domain' => (string) parse_url($baseUrl, PHP_URL_HOST),
                'path' => '/',
            ];
        }

        foreach ((array) $this->option('header') as $header) {
            if (! is_string($header) || ! str_contains($header, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $header, 2);
            $name = trim($name);

            if ($name === '' || $this->blockedHeader($name)) {
                continue;
            }

            $headers[$name] = trim($value);
        }

        $storageState = $auth['storage_state'] ?? null;

        return [
            'cookies' => $cookies,
            'headers' => $headers,
            'storageState' => is_string($storageState) && $storageState !== '' ? $storageState : null,
        ];
    }

    /**
     * @param  mixed  $cookies
     * @return list<array<string, mixed>>
     */
    private function configuredCookies(mixed $cookies): array
    {
        if (! is_array($cookies)) {
            return [];
        }

        $allowed = ['name', 'value', 'url', 'domain', 'path', 'expires', 'httpOnly', 'secure', 'sameSite'];
        $normalized = [];

        foreach ($cookies as $cookie) {
            if (! is_array($cookie)) {
                continue;
            }

            $filtered = array_intersect_key($cookie, array_flip($allowed));

            if (! isset($filtered['name'], $filtered['value'])) {
                continue;
            }

            $normalized[] = $filtered;
        }

        return $normalized;
    }

    /**
     * @param  mixed  $headers
     * @return array<string, string>
     */
    private function configuredHeaders(mixed $headers): array
    {
        if (! is_array($headers)) {
            return [];
        }

        $normalized = [];

        foreach ($headers as $name => $value) {
            if (! is_string($name) || $this->blockedHeader($name)) {
                continue;
            }

            if (is_string($value) || is_numeric($value)) {
                $normalized[$name] = (string) $value;
            }
        }

        return $normalized;
    }

    private function blockedHeader(string $name): bool
    {
        return in_array(strtolower($name), ['host', 'content-length', 'transfer-encoding', 'connection', 'upgrade'], true);
    }

    /**
     * @return array<string, array<string, mixed>>|null
     */
    private function readCaptures(Filesystem $files, string $path): ?array
    {
        if (! $files->exists($path)) {
            return null;
        }

        $decoded = json_decode($files->get($path), true);

        if (! is_array($decoded) || ($decoded !== [] && array_is_list($decoded))) {
            return null;
        }

        $captures = [];

        foreach ($decoded as $name => $capture) {
            if (is_string($name) && is_array($capture) && ! array_is_list($capture)) {
                $captures[$name] = $capture;
            }
        }

        return $captures;
    }
}
