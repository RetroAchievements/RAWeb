<?php

declare(strict_types=1);

use App\Console\Commands\GenerateV2OpenApiSpec;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

beforeEach(function () {
    $this->publicPath = sys_get_temp_dir() . '/' . uniqid('openapi-public-', true);
    app()->usePublicPath($this->publicPath);
    $this->specPath = $this->publicPath . '/' . GenerateV2OpenApiSpec::PATH;

    File::ensureDirectoryExists(dirname($this->specPath));
});

afterEach(function () {
    File::deleteDirectory($this->publicPath);
});

function generate(bool $check = false): PendingCommand
{
    /** @var TestCase $context */
    $context = test();

    return $context->artisan('ra:api:generate-openapi-spec' . ($check ? ' --check' : ''));
}

function specAt(string $path): array
{
    return json_decode(File::get($path), true);
}

function sharedSpecPath(): string
{
    static $path = null;

    if ($path === null) {
        /** @var object{specPath: string} $fixtures */
        $fixtures = test();

        $stable = sys_get_temp_dir() . '/' . uniqid('openapi-shared-', true) . '.json';

        generate()->assertSuccessful();
        File::copy($fixtures->specPath, $stable);

        $path = $stable;
    }

    return $path;
}

function sharedSpec(): array
{
    static $document = null;

    return $document ??= specAt(sharedSpecPath());
}

/**
 * @return array<string, array<string, mixed>>
 */
function resourceAttributes(string $type): array
{
    return sharedSpec()['components']['schemas']["resources.{$type}.resource.fetch"]['properties']['attributes']['properties'];
}

it('given the command runs, it writes the spec', function () {
    // Act
    generate()->assertSuccessful();

    // Assert
    expect(File::exists($this->specPath))->toBeTrue();
    expect(specAt($this->specPath)['openapi'])->toEqual('3.0.2');
});

it('given routes that no schema describes, it documents them from their response classes', function () {
    // Assert
    expect(sharedSpec()['paths'])
        ->toHaveKey('/health')
        ->toHaveKey('/games/{gameId}/achievement-distribution')
        ->toHaveKey('/event-achievements/achievement-of-the-week');
});

it('given resource routes, it documents them', function () {
    // Assert
    expect(sharedSpec()['paths'])
        ->toHaveKey('/games')
        ->toHaveKey('/games/{game}');
});

it('given the spec is written, it holds no example values', function () {
    // Assert
    $spec = File::get(sharedSpecPath());

    expect($spec)->not->toContain('"example"');
    expect($spec)->not->toContain('"examples"');
});

it('given the spec is written, it holds no environment specific values', function () {
    // Assert
    expect(File::get(sharedSpecPath()))->not->toContain('localhost');
    expect(sharedSpec()['servers'][0]['url'])
        ->toEqual('https://api.retroachievements.org/api/v2');
});

it('given every documented operation, each one names the OAuth scope it needs', function () {
    // Act
    $scopeless = [];
    foreach (sharedSpec()['paths'] as $path => $operations) {
        foreach ($operations as $method => $operation) {
            foreach ($operation['security'] ?? [] as $requirement) {
                if (array_key_exists('OAuth2', $requirement) && $requirement['OAuth2'] === []) {
                    $scopeless[] = "{$method} {$path}";
                }
            }
        }
    }

    // Assert
    expect($scopeless)->toEqual([]);
});

it('given an endpoint that declares its own scope, it documents that scope', function () {
    // Assert
    expect(sharedSpec()['paths']['/users/{user}/followers']['get']['security'])
        ->toEqual([['ApiKey' => []], ['OAuth2' => ['follows:read']]]);
});

it('given an endpoint behind the baseline read gate, it documents the baseline read scope', function () {
    // Assert
    expect(sharedSpec()['paths']['/games']['get']['security'])
        ->toEqual([['ApiKey' => []], ['OAuth2' => ['data:read']]]);
});

it('uses absolute OAuth URLs', function () {
    // Act
    $flow = sharedSpec()['components']['securitySchemes']['OAuth2']['flows']['authorizationCode'];

    // Assert
    expect($flow['authorizationUrl'])->toStartWith('https://');
    expect($flow['tokenUrl'])->toStartWith('https://');
});

it('given the committed spec is current, check succeeds', function () {
    // Arrange
    File::copy(sharedSpecPath(), $this->specPath);

    // Assert
    generate(check: true)->assertSuccessful();
});

it('given the committed spec lacks a path, check reports the add', function () {
    // Arrange
    $stale = sharedSpec();
    unset($stale['paths']['/systems']);
    File::put($this->specPath, json_encode($stale, JSON_PRETTY_PRINT) . "\n");

    // Assert
    generate(check: true)
        ->expectsOutputToContain('added:   /systems')
        ->assertFailed();
});

it('given the committed spec holds a path the code dropped, check reports the removal', function () {
    // Arrange
    $stale = sharedSpec();
    $stale['paths']['/no-such-endpoint'] = $stale['paths']['/systems'];
    File::put($this->specPath, json_encode($stale, JSON_PRETTY_PRINT) . "\n");

    // Assert
    generate(check: true)
        ->expectsOutputToContain('removed: /no-such-endpoint')
        ->assertFailed();
});

it('given a field inside a path changed, check fails', function () {
    // Arrange
    $stale = sharedSpec();
    $stale['paths']['/systems']['get']['summary'] = 'Something the generator never wrote';
    File::put($this->specPath, json_encode($stale, JSON_PRETTY_PRINT) . "\n");

    // Assert
    generate(check: true)->assertFailed();
});

it('given a spec that will not parse, check fails', function () {
    // Arrange
    File::put($this->specPath, 'not json');

    // Assert
    generate(check: true)
        ->expectsOutputToContain('not valid JSON')
        ->assertFailed();
});

it('given a schema that declares nullability, it flags only the attributes that can be null', function () {
    // Assert
    $attributes = resourceAttributes('games');

    expect($attributes['releasedAt']['nullable'])->toEqual(true);
    expect($attributes['title'])->not->toHaveKey('nullable');
});

it('given a whole number field, it documents an integer rather than a number', function () {
    // Assert
    expect(resourceAttributes('games')['playersTotal']['type'])->toEqual('integer');
    expect(resourceAttributes('achievements')['unlockPercentage']['type'])->toEqual('number');
});
