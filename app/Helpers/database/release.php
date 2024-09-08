<?php

use App\Models\Emulator;
use App\Models\System;
use Illuminate\Support\Facades\Log;

/**
 * References:
 * https://github.com/RetroAchievements/RAInterface/blob/master/RA_Interface.h
 * https://github.com/RetroAchievements/rcheevos/blob/develop/include/rconsoles.h
 * https://github.com/RetroAchievements/rcheevos/blob/develop/src/rcheevos/consoleinfo.c
 * https://github.com/RetroAchievements/rcheevos/blob/develop/test/rcheevos/test_consoleinfo.c
 */
function getValidConsoleIds(): array
{
    return Cache::store('array')->rememberForever('system:active-ids', function () {
        return System::active()->pluck('ID')->toArray();
    });
}

function isValidConsoleId(int $consoleId): bool
{
    // This function is called A LOT - as many as four or five times per console that
    // the player has loaded a game for when rendering their profile. It takes roughly
    // the same amount of time to query all active IDs as it does to query each individual
    // ID, so fetch them all once per request, even if we only ever need one.
    return in_array($consoleId, getValidConsoleIds());
}

function getEmulatorReleaseByIntegrationId(?int $integrationId): ?array
{
    $releases = getReleasesFromFile();
    $emulators = $releases['emulators'] ?? [];

    return $emulators[$integrationId] ?? null;
}

function getIntegrationRelease(): ?array
{
    $releases = getReleasesFromFile();

    return $releases['integration'] ?? null;
}

function getReleasesFromFile(): ?array
{
    try {
        return file_exists(storage_path('app/releases.php')) ? require storage_path('app/releases.php') : null;
    } catch (Throwable $throwable) {
        if (app()->environment('local')) {
            throw $throwable;
        }
        Log::warning($throwable->getMessage());
    }

    return [];
}

function getActiveEmulatorReleases(): array
{
    $result = [];

    // TODO: migrate remaining data out of file
    $releases = getReleasesFromFile();

    $emulators = Emulator::active()->orderBy('name')->get();
    foreach ($emulators as &$emulator) {
        $systems = $emulator->systems()->active()->orderBy('Name')->pluck('Name')->toArray();
        if (!empty($systems)) {
            $entry = [
                'name' => $emulator->name,
                'original_name' => $emulator->original_name,
                'description' => $emulator->description,
                'documentation_url' => $emulator->documentation_url,
                'download_url' => $emulator->download_url,
                'source_url' => $emulator->source_url,
                'systems' => $systems,
            ];

            $release = null;
            foreach ($releases['emulators'] as $scan) {
                if ($scan['handle'] === $emulator->name) {
                    $release = $scan;
                    break;
                }
            }

            if ($release !== null) {
                // TODO: migrate these out of file
                if (array_key_exists('minimum_version', $release)) {
                    $entry['minimum_version'] = $release['minimum_version'];
                }
                if (array_key_exists('latest_version', $release)) {
                    $entry['latest_version'] = $release['latest_version'];
                }
                if (array_key_exists('latest_version_url', $release)) {
                    $entry['latest_version_url'] = $release['latest_version_url'];
                }
                if (array_key_exists('latest_version_url_x64', $release)) {
                    $entry['latest_version_url_x64'] = $release['latest_version_url_x64'];
                }
            }

            $result[] = $entry;
        }
    }

    return $result;
}
