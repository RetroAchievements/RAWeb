<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use LaravelJsonApi\OpenApiSpec\OpenApiGenerator;

class GenerateV2OpenApiSpec extends Command
{
    protected $signature = 'ra:api:generate-openapi-spec
        {--check : Report whether the committed spec is out of date, and change nothing}';

    protected $description = 'Generate the OpenAPI spec for the V2 API';

    public const PATH = 'openapi/v2.json';

    public function handle(): int
    {
        $spec = $this->build();

        if ($spec === null) {
            return self::FAILURE;
        }

        $destination = public_path(self::PATH);

        if ($this->option('check')) {
            return $this->reportDrift($destination, $spec);
        }

        File::ensureDirectoryExists(dirname($destination));
        File::put($destination, $spec);

        $this->info('Wrote ' . $this->relativeLabel($destination));

        return self::SUCCESS;
    }

    /**
     * @return string|null the serialized document, or null when it is incomplete
     */
    private function build(): ?string
    {
        $generator = app(OpenApiGenerator::class);
        $generated = $generator->generate('v2', 'json');

        if (!$this->reportSkippedRoutes($generator)) {
            return null;
        }

        $document = json_decode($generated, false, 512, JSON_THROW_ON_ERROR);

        return json_encode(
            $document,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ) . "\n";
    }

    private function reportSkippedRoutes(OpenApiGenerator $generator): bool
    {
        $skipped = $generator->skippedRoutes();

        if ($skipped === []) {
            return true;
        }

        $this->error('The generator could not describe every route, so the spec would be incomplete:');
        $this->newLine();

        foreach ($skipped as $route) {
            $this->line("  {$route['uri']} ({$route['route']})");
            $this->line("    {$route['reason']}");
        }

        return false;
    }

    private function reportDrift(string $destination, string $spec): int
    {
        $label = $this->relativeLabel($destination);

        if (!File::exists($destination)) {
            $this->error($label . ' is missing. Run: php artisan ra:api:generate-openapi-spec');

            return self::FAILURE;
        }

        $committed = File::get($destination);

        if ($committed === $spec) {
            $this->info($label . ' is up to date.');

            return self::SUCCESS;
        }

        $this->error($label . ' is out of date. Run: php artisan ra:api:generate-openapi-spec');
        $this->newLine();

        foreach ($this->describeDrift($committed, $spec) as $line) {
            $this->line('  ' . $line);
        }

        return self::FAILURE;
    }

    /**
     * @return string[]
     */
    private function describeDrift(string $committed, string $spec): array
    {
        $committedPaths = $this->pathsOf($committed);

        if ($committedPaths === null) {
            return ['The committed file is not valid JSON, so it cannot be compared path by path.'];
        }

        $current = $this->pathsOf($spec) ?? [];

        $lines = [];

        foreach (array_diff($current, $committedPaths) as $path) {
            $lines[] = "added:   {$path}";
        }

        foreach (array_diff($committedPaths, $current) as $path) {
            $lines[] = "removed: {$path}";
        }

        if ($lines === []) {
            $lines[] = 'The set of paths is unchanged, so an existing path was modified.';
        }

        return $lines;
    }

    /**
     * @return string[]|null the path keys, or null when the document will not parse
     */
    private function pathsOf(string $document): ?array
    {
        $decoded = json_decode($document, true);

        if (!is_array($decoded)) {
            return null;
        }

        $paths = $decoded['paths'] ?? [];

        return is_array($paths) ? array_keys($paths) : [];
    }

    private function relativeLabel(string $destination): string
    {
        $public = public_path('');

        return str_starts_with($destination, $public)
            ? ltrim(substr($destination, strlen($public)), DIRECTORY_SEPARATOR)
            : $destination;
    }
}
