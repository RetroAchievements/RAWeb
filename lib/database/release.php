<?php

use RA\Emulators;

require_once(__DIR__ . '/../bootstrap.php');

/**
 * @param int $consoleId
 * @return bool
 */
function isValidConsoleId($consoleId)
{
    switch ($consoleId) {
        case 1: // Mega Drive/Genesis
        case 2: // N64
        case 3: // Super Nintendo
        case 4: // Gameboy
        case 5: // Gameboy Advance
        case 6: // Gameboy Color
        case 7: // NES
        case 8: // PC Engine
        case 9: // Sega CD
        case 10: // Sega 32X
        case 11: // Master System
        case 12: // PlayStation
        case 13: // Atari Lynx
        case 14: // Neo Geo Pocket
        case 15: // Game Gear
            // case 16: //
        case 17: // Atari Jaguar
        case 18: // Nintendo DS
            // case 19: //
            // case 20: //
            // case 21: //
            // case 22: //
            // case 23: // Unused
        case 24: // Pokemon Mini
        case 25: // Atari 2600
            // case 26: //
        case 27: // Arcade
        case 28: // Virtual Boy
            // case 29: //
            // case 30: //
            // case 31: //
            // case 32: //
        case 33: // SG-1000
            // case 34: //
            // case 35: //
            // case 36: //
            // case 37: //
        case 38: // Apple II
        case 39: // Sega Saturn
            // case 40: //
            // case 41: //
            // case 42: //
            // case 43: //
        case 44: // ColecoVision
            // case 45: //
            // case 46: //
        case 47: // PC-8800
            // case 48: //
            // case 49: //
            // case 50: //
        case 51: // Atari7800
            // case 52: //
        case 53: // WonderSwan
            // case 54: //
            // case 55: //
            // case 100: // Hubs (not an actual console)
        case 101: // Events (not an actual console)
            return true;
    }
    return false;
}

/**
 * @param int $consoleId
 * @return mixed|null
 */
function getEmulatorIdByConsoleId($consoleId)
{
    $consoleMap = [
        1 => Emulators::RAGens,
        2 => Emulators::RAP64,
        3 => Emulators::RASnes9x,
        4 => Emulators::RAVBA,
        7 => Emulators::RANes,
        8 => Emulators::RAPCE,
        11 => Emulators::RAMeka,
        25 => Emulators::RALibretro, // Atari 2600 TODO: is this used?
        38 => Emulators::RAppleWin,
        47 => Emulators::RAQUASI88,
    ];
    return $consoleMap[$consoleId] ?? null;
}

/**
 * @param int $consoleId
 * @return array|null
 */
function getEmulatorReleaseByConsoleId($consoleId)
{
    $emulatorId = getEmulatorIdByConsoleId($consoleId);
    return $emulatorId === null ? null : getEmulatorReleaseByIntegrationId($emulatorId);
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
    return file_exists(__DIR__ . '/releases.php') ? require_once(__DIR__ . '/releases.php') : null;
}

function getActiveEmulatorReleases()
{
    $consoles = getConsoleList();
    $releases = getReleasesFromFile();
    $emulators = array_filter($releases['emulators'] ?? [], function ($emulator) {
        return $emulator['active'] ?? false;
    });
    $emulators = array_map(function ($emulator) use ($consoles) {
        $systems = [];
        foreach ($emulator['systems'] as $system) {
            $systems[$system] = $consoles[$system];
        }
        $emulator['systems'] = $systems;
        return $emulator;
    }, $emulators);
    return $emulators;
}
