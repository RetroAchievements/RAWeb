<?php

/**
 * References:
 * https://github.com/RetroAchievements/RAInterface/blob/master/RA_Interface.h
 * https://github.com/RetroAchievements/rcheevos/blob/develop/include/rconsoles.h
 * https://github.com/RetroAchievements/rcheevos/blob/develop/src/rcheevos/consoleinfo.c
 * https://github.com/RetroAchievements/rcheevos/blob/develop/test/rcheevos/test_consoleinfo.c
 *
 * @param int $consoleId
 * @return bool
 */
function isValidConsoleId($consoleId)
{
    switch ($consoleId) {
        case 1: // Mega Drive/Genesis
        case 2: // Nintendo 64
        case 3: // SNES
        case 4: // Game Boy
        case 5: // Game Boy Advance
        case 6: // Game Boy Color
        case 7: // NES
        case 8: // PC Engine
        case 9: // Sega CD
        case 10: // Sega 32X
        case 11: // Master System
        case 12: // PlayStation
        case 13: // Atari Lynx
        case 14: // Neo Geo Pocket
        case 15: // Game Gear
            // case 16: // GameCube
        case 17: // Atari Jaguar
        case 18: // Nintendo DS
            // case 19: // Wii
            // case 20: // Wii U
            // case 21: // PlayStation 2
            // case 22: // Xbox
        case 23: // Magnavox Odyssey 2
        case 24: // Pokemon Mini
        case 25: // Atari 2600
            // case 26: // DOS
        case 27: // Arcade
        case 28: // Virtual Boy
        case 29: // MSX
            // case 30: // Commodore 64
            // case 31: // ZX81
            // case 32: // Oric
        case 33: // SG-1000
            // case 34: // VIC-20
            // case 35: // Amiga
            // case 36: // Atari ST
            // case 37: // Amstrad CPC
        case 38: // Apple II
        case 39: // Sega Saturn
            // case 40: // Dreamcast
            // case 41: // PlayStation Portable
            // case 42: // Philips CD-i
        case 43: // 3DO Interactive Multiplayer
        case 44: // ColecoVision
        case 45: // Intellivision
        case 46: // Vectrex
        case 47: // PC-8000/8800
            // case 48: // PC-9800
        case 49: // PC-FX
            // case 50: // Atari 5200
        case 51: // Atari 7800
            // case 52: // X68K
        case 53: // WonderSwan
            // case 54: // Cassette Vision
            // case 55: // Super Cassette Vision
        case 56: // Neo Geo CD
            // case 57: // Fairchild Channel-F
            // case 58: // FM Towns
            // case 59: // ZX Spectrum
            // case 60: // Game & Watch
            // case 61: // Nokia N-Gage
            // case 62: // Nintendo 3DS
        case 63: // Supervision
            // case 64: // Sharp X1
            // case 65: // TIC-80
            // case 66: // Thomson TO8
            // case 100: // Hubs (not an actual console)
        case 101: // Events (not an actual console)
            return true;
    }

    return false;
}

/**
 * @param int $integrationId
 * @return array|null
 */
function getEmulatorReleaseByIntegrationId($integrationId)
{
    $releases = getReleasesFromFile();
    $emulators = $releases['emulators'] ?? [];

    return $emulators[$integrationId] ?? null;
}

/**
 * @return array|null
 */
function getIntegrationRelease()
{
    $releases = getReleasesFromFile();

    return $releases['integration'] ?? null;
}

/**
 * @return array|null
 */
function getReleasesFromFile()
{
    return file_exists(__DIR__ . '/releases.php') ? require_once __DIR__ . '/releases.php' : null;
}

/**
 * @return array
 */
function getActiveEmulatorReleases()
{
    $consoles = getConsoleList();
    $releases = getReleasesFromFile();
    $emulators = array_filter($releases['emulators'] ?? [], function ($emulator) {
        return $emulator['active'] ?? false;
    });
    if (!empty($consoles)) {
        $emulators = array_map(function ($emulator) use ($consoles) {
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
