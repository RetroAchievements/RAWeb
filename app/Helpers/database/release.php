<?php

use App\Models\System;
use App\Support\Cache\CacheKey;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * References:
 * https://github.com/RetroAchievements/RAInterface/blob/master/RA_Interface.h
 * https://github.com/RetroAchievements/rcheevos/blob/develop/include/rconsoles.h
 * https://github.com/RetroAchievements/rcheevos/blob/develop/src/rcheevos/consoleinfo.c
 * https://github.com/RetroAchievements/rcheevos/blob/develop/test/rcheevos/test_consoleinfo.c
 */
function isValidConsoleId(int $consoleId): bool
{
    $validConsoleIds = Cache::store('array')->rememberForever('system:active_ids', function()
    {
        return System::active()->pluck('ID')->toArray();
    });

    return in_array($consoleId, $validConsoleIds);
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
    $consoles = System::get(['ID', 'Name'])->keyBy('ID')->map(fn ($system) => $system['Name']);
    $releases = getReleasesFromFile();
    $emulators = array_filter($releases['emulators'] ?? [], fn ($emulator) => $emulator['active'] ?? false);
    if ($consoles->isNotEmpty()) {
        return array_map(function ($emulator) use ($consoles) {
            $systems = [];
            foreach ($emulator['systems'] as $system) {
                $systems[$system] = $consoles[$system];
            }
            $emulator['systems'] = $systems;

            return $emulator;
        }, $emulators);
    }

    return $emulators;
}
