<?php

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
    $consoles = System::active()->get(['ID', 'Name'])->keyBy('ID')->map(fn ($system) => $system['Name'])->toArray();
    $releases = getReleasesFromFile();
    $emulators = array_filter($releases['emulators'] ?? [], fn ($emulator) => $emulator['active'] ?? false);
    if (!empty($consoles)) {
        return array_map(function ($emulator) use ($consoles) {
            $systems = [];
            foreach ($emulator['systems'] as $system) {
                if (array_key_exists($system, $consoles)) {
                    $systems[$system] = $consoles[$system];
                }
            }
            $emulator['systems'] = $systems;

            return $emulator;
        }, $emulators);
    }

    return $emulators;
}
