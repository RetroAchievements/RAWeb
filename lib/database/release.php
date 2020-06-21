<?php

use RA\Emulators;

/**
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
            // case 23: // Unused
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
            // case 43: // 3DO Interactive Multiplayer
        case 44: // ColecoVision
            // case 45: // Intellivision
        case 46: // Vectrex
        case 47: // PC-8000/8800
            // case 48: // PC-9800
            // case 49: // PC-FX
            // case 50: // Atari 5200
        case 51: // Atari 7800
            // case 52: // X68K
        case 53: // WonderSwan
            // case 54: // Cassette Vision
            // case 55: // Super Cassette Vision
            // case 100: // Hubs (not an actual console)
        case 101: // Events (not an actual console)
            return true;
    }
    return false;
}

/**
 * Clients used to query for the latest build version by console id instead of emulator id.
 * For new emulators/clients it should not be necessary to add them to this mapping.
 * This mapping of console id to emulator id only exists for legacy reasons.
 *
 * @param int $consoleId
 * @return mixed|null
 */
function getEmulatorIdByConsoleId($consoleId)
{
    $consoleMap = [
        1 => Emulators::RAGens, // Mega Drive/Genesis
        2 => Emulators::RAP64, // Nintendo 64
        3 => Emulators::RASnes9x, // SNES
        4 => Emulators::RAVBA, // Game Boy
        5 => null, // Game Boy Advance
        6 => null, // Game Boy Color
        7 => Emulators::RANes, // NES
        8 => Emulators::RAPCE, // PC Engine
        9 => null, // Sega CD
        10 => null, // Sega 32X
        11 => Emulators::RAMeka, // Master System
        12 => null, // PlayStation
        13 => null, // Atari Lynx
        14 => null, // Neo Geo Pocket
        15 => null, // Game Gear
        16 => null, // GameCube
        17 => null, // Atari Jaguar
        18 => null, // Nintendo DS
        19 => null, // Wii
        20 => null, // Wii U
        21 => null, // PlayStation 2
        22 => null, // Xbox
        23 => null, // Unused
        24 => null, // Pokemon Mini
        25 => Emulators::RALibretro, // Atari 2600 TODO: is this used?
        26 => null, // DOS
        27 => null, // Arcade
        28 => null, // Virtual Boy
        29 => null, // MSX
        30 => null, // Commodore 64
        31 => null, // ZX81
        32 => null, // Oric
        33 => null, // SG-1000
        34 => null, // VIC-20
        35 => null, // Amiga
        36 => null, // Atari ST
        37 => null, // Amstrad CPC
        38 => Emulators::RAppleWin, // Apple II
        39 => null, // Sega Saturn
        40 => null, // Dreamcast
        41 => null, // PlayStation Portable
        42 => null, // Philips CD-i
        43 => null, // 3DO Interactive Multiplayer
        44 => null, // ColecoVision
        45 => null, // Intellivision
        46 => null, // Vectrex
        47 => Emulators::RAQUASI88, // PC-8000/8800
        48 => null, // PC-9800
        49 => null, // PC-FX
        50 => null, // Atari 5200
        51 => null, // Atari 7800
        52 => null, // X68K
        53 => null, // WonderSwan
        54 => null, // Cassette Vision
        55 => null, // Super Cassette Vision
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
