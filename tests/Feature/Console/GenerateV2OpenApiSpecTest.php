<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

/**
 * NOTE: these tests generate a file into a temporary path.
 */
beforeEach(function () {
    $this->specPath = sys_get_temp_dir() . '/' . uniqid('openapi-', true) . '.json';
});

afterEach(function () {
    File::delete($this->specPath);
});

function generate(string $output, bool $check = false): PendingCommand
{
    /** @var TestCase $context */
    $context = test();

    return $context->artisan(
        'ra:api:generate-openapi-spec --output=' . $output . ($check ? ' --check' : ''),
    );
}

function specAt(string $path): array
{
    return json_decode(File::get($path), true);
}

it('given the command runs, it writes the spec to the requested path', function () {
    // Act
    generate($this->specPath)->assertSuccessful();

    // Assert
    expect(File::exists($this->specPath))->toBeTrue();
    expect(specAt($this->specPath)['openapi'])->toEqual('3.0.2');
});

it('given routes that no schema describes, it documents them from their response classes', function () {
    // Act
    generate($this->specPath)->assertSuccessful();

    // Assert
    expect(specAt($this->specPath)['paths'])
        ->toHaveKey('/health')
        ->toHaveKey('/games/{gameId}/achievement-distribution')
        ->toHaveKey('/event-achievements/achievement-of-the-week');
});

it('given resource routes, it documents them', function () {
    // Act
    generate($this->specPath)->assertSuccessful();

    // Assert
    expect(specAt($this->specPath)['paths'])
        ->toHaveKey('/games')
        ->toHaveKey('/games/{game}');
});

it('given the spec is written, it holds no example values', function () {
    // Act
    generate($this->specPath)->assertSuccessful();

    // Assert
    expect(File::get($this->specPath))->not->toContain('"example"');
    expect(File::get($this->specPath))->not->toContain('"examples"');
});

it('given the spec is written, it holds no environment specific values', function () {
    // Act
    generate($this->specPath)->assertSuccessful();

    // Assert
    expect(File::get($this->specPath))->not->toContain('localhost');
    expect(specAt($this->specPath)['servers'][0]['variables']['serverUrl']['default'])
        ->toEqual('https://api.retroachievements.org/api/v2');
});

it('given every documented operation, each one names the OAuth scope it needs', function () {
    // Arrange
    generate($this->specPath)->assertSuccessful();

    // Act
    $scopeless = [];
    foreach (specAt($this->specPath)['paths'] as $path => $operations) {
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
    // Arrange
    generate($this->specPath)->assertSuccessful();

    // Assert
    expect(specAt($this->specPath)['paths']['/users/{user}/followers']['get']['security'])
        ->toEqual([['ApiKey' => []], ['OAuth2' => ['follows:read']]]);
});

it('given an endpoint behind the baseline read gate, it documents the baseline read scope', function () {
    // Arrange
    generate($this->specPath)->assertSuccessful();

    // Assert
    expect(specAt($this->specPath)['paths']['/games']['get']['security'])
        ->toEqual([['ApiKey' => []], ['OAuth2' => ['data:read']]]);
});

it('uses absolute OAuth URLs', function () {
    // Arrange
    generate($this->specPath)->assertSuccessful();

    // Act
    $flow = specAt($this->specPath)['components']['securitySchemes']['OAuth2']['flows']['authorizationCode'];

    // Assert
    expect($flow['authorizationUrl'])->toStartWith('https://');
    expect($flow['tokenUrl'])->toStartWith('https://');
});

it('given the committed spec is current, check succeeds', function () {
    // Arrange
    generate($this->specPath)->assertSuccessful();

    // Assert
    generate($this->specPath, check: true)->assertSuccessful();
});

it('given the committed spec lacks a path, check reports the add', function () {
    // Arrange
    generate($this->specPath)->assertSuccessful();

    $stale = specAt($this->specPath);
    unset($stale['paths']['/systems']);
    File::put($this->specPath, json_encode($stale, JSON_PRETTY_PRINT) . "\n");

    // Assert
    generate($this->specPath, check: true)
        ->expectsOutputToContain('added:   /systems')
        ->assertFailed();
});

it('given the committed spec holds a path the code dropped, check reports the removal', function () {
    // Arrange
    generate($this->specPath)->assertSuccessful();

    $stale = specAt($this->specPath);
    $stale['paths']['/no-such-endpoint'] = $stale['paths']['/systems'];
    File::put($this->specPath, json_encode($stale, JSON_PRETTY_PRINT) . "\n");

    // Assert
    generate($this->specPath, check: true)
        ->expectsOutputToContain('removed: /no-such-endpoint')
        ->assertFailed();
});

it('given a field inside a path changed, check fails', function () {
    // Arrange
    generate($this->specPath)->assertSuccessful();

    $stale = specAt($this->specPath);
    $stale['paths']['/systems']['get']['summary'] = 'Something the generator never wrote';
    File::put($this->specPath, json_encode($stale, JSON_PRETTY_PRINT) . "\n");

    // Assert
    generate($this->specPath, check: true)->assertFailed();
});

it('given a spec that will not parse, check fails', function () {
    // Arrange
    File::put($this->specPath, 'not json');

    // Assert
    generate($this->specPath, check: true)
        ->expectsOutputToContain('not valid JSON')
        ->assertFailed();
});
