<?php

use Illuminate\Support\Facades\Log;
use LegacyApp\Platform\Models\System;

/**
 * References:
 * https://github.com/RetroAchievements/RAInterface/blob/master/RA_Interface.h
 * https://github.com/RetroAchievements/rcheevos/blob/develop/include/rconsoles.h
 * https://github.com/RetroAchievements/rcheevos/blob/develop/src/rcheevos/consoleinfo.c
 * https://github.com/RetroAchievements/rcheevos/blob/develop/test/rcheevos/test_consoleinfo.c
 */
function isValidConsoleId(int $consoleId): bool
{
    return match ($consoleId) {
        1, // Mega Drive/Genesis
        2, // Nintendo 64
        3, // SNES
        4, // Game Boy
        5, // Game Boy Advance
        6, // Game Boy Color
        7, // NES
        8, // PC Engine
        9, // Sega CD
        10, // Sega 32X
        11, // Master System
        12, // PlayStation
        13, // Atari Lynx
        14, // Neo Geo Pocket
        15, // Game Gear
        // 16, // GameCube
        17, // Atari Jaguar
        18, // Nintendo DS
        // 19, // Wii
        // 20, // Wii U
        21, // PlayStation 2
        // 22, // Xbox
        23, // Magnavox Odyssey 2
        24, // Pokemon Mini
        25, // Atari 2600
        // 26, // DOS
        27, // Arcade
        28, // Virtual Boy
        29, // MSX
        // 30, // Commodore 64
        // 31, // ZX81
        // 32, // Oric
        33, // SG-1000
        // 34, // VIC-20
        // 35, // Amiga
        // 36, // Atari ST
        37, // Amstrad CPC
        38, // Apple II
        39, // Sega Saturn
        40, // Dreamcast
        41, // PlayStation Portable
        // 42, // Philips CD-i
        43, // 3DO Interactive Multiplayer
        44, // ColecoVision
        45, // Intellivision
        46, // Vectrex
        47, // PC-8000/8800
        // 48, // PC-9800
        49, // PC-FX
        // 50, // Atari 5200
        51, // Atari 7800
        // 52, // X68K
        53, // WonderSwan
        // 54, // Cassette Vision
        // 55, // Super Cassette Vision
        56, // Neo Geo CD
        57, // Fairchild Channel-F
        // 58, // FM Towns
        // 59, // ZX Spectrum
        // 60, // Game & Watch
        // 61, // Nokia N-Gage
        // 62, // Nintendo 3DS
        63, // Supervision
        // 64, // Sharp X1
        // 65, // TIC-80
        // 66, // Thomson TO8
        // 67, // PC-6000
        // 68, // Sega Pico
        69, // Mega Duck
        // 70, // Zeebo
        71, // Arduboy
        72, // WASM-4
        73, // Arcadia 2001
        74, // Interton VC 4000
        75, // Elektor TV Games Computer
        76, // PC Engine CD
        77, // Atari Jaguar CD
        78, // Nintendo DSi
        // 79, // TI-83
        // 80, // Uzebox
        // 100, // Hubs (not an actual console)
        101 => true, // Events (not an actual console)
        default => false,
    };
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
        return file_exists(storage_path('app/releases.php')) ? require_once storage_path('app/releases.php') : null;
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
