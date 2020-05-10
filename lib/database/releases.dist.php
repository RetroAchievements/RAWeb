<?php

use RA\Emulators;

return [
    'integration' => [
        'minimum_version' => '0.77.2',
        'latest_version' => '0.77.3',
        'latest_version_url' => 'bin/RA_Integration.dll',
        'latest_version_url_x64' => 'bin/RA_Integration-x64.dll',
    ],
    'emulators' => [
        Emulators::RAppleWin => [
            'minimum_version' => '1.1.1',
            'latest_version' => '1.1.1',
            'latest_version_url' => 'bin/RAppleWin.zip',
            // 'latest_version_url_x64' => 'bin/RAppleWin-x64.zip',
            'name' => 'AppleWin',
            'handle' => 'RAppleWin',
            'active' => true,
            'integration_id' => Emulators::RAppleWin,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'systems' => [
                38, // Apple II
            ],
        ],
        Emulators::RAGens => [
            'minimum_version' => '0.058',
            'latest_version' => '0.058',
            'latest_version_url' => 'bin/RAGens.zip',
            // 'latest_version_url_x64' => 'bin/RAGens-x64.zip',
            'name' => 'Gens',
            'handle' => 'RAGens',
            'active' => true,
            'integration_id' => Emulators::RAGens,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'systems' => [
                1, // Genesis/Mega Drive
            ],
        ],
        Emulators::RALibretro => [
            'minimum_version' => '1.2',
            'latest_version' => '1.2',
            'latest_version_url' => 'bin/RALibretro.zip',
            'latest_version_url_x64' => 'bin/RALibretro-x64.zip',
            'name' => 'LibRetro',
            'handle' => 'RALibRetro',
            'active' => true,
            'integration_id' => Emulators::RALibretro,
            'link' => 'https://docs.retroachievements.org/RALibretro/',
            'description' => 'RALibRetro is a multi-emulator that can be used for achievements development.',
            'systems' => [
                1, // Genesis/Mega Drive
                3, // SNES (Cores: Snes9x, bsnes)
                4, // Game Boy (Cores: Gambatte, Gearboy, SameBoy)
                5, // Game Boy Advance (Cores: mGBA, VBA Next, VBA-M, Beetle GBA)
                6, // Game Boy Color (Cores: Gambatte, Gearboy, SameBoy)
                7, // NES (Cores: Mesen, FCEUmm, QuickNES)
                8, // PC Engine (Cores: Beetle PCE Fast, Beetle SGX)
                9, // Sega CD
                10, // 32X
                11, // Master System / MegaDrive - Genesis (Cores: Gearsystem, Genesis Plus GX, Picodrive)
                12, // PlayStation
                13, // Atari Lynx (Cores: Handy, Beetle Handy)
                14, // Neo Geo Pocket (Cores: Beetle NeoPop)
                15, // Game Gear
                17, // Atari Jaguar
                18, // Nintendo DS
                27, // Arcade (Cores: FB Alpha)
                24, // Pokemon Mini
                25, // Atari 2600 (Cores: Stella)
                28, // Virtual Boy (Cores: Beetle VB)
                33, // SG-1000
                39, // Saturn
                44, // ColecoVision
                51, // Atari 7800
                53, // WonderSwan
            ],
        ],
        Emulators::RAMeka => [
            'minimum_version' => '0.023',
            'latest_version' => '0.023',
            'latest_version_url' => 'bin/RAMeka.zip',
            // 'latest_version_url_x64' => 'bin/RAMeka-x64.zip',
            'name' => 'Meka',
            'handle' => 'RAMeka',
            'active' => true,
            'integration_id' => Emulators::RAMeka,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'systems' => [
                11, // Master System
                15, // Game Gear
                33, // SG-1000
                44, // ColecoVision
            ],
        ],
        Emulators::RANes => [
            'minimum_version' => '0.17',
            'latest_version' => '0.17',
            'latest_version_url' => 'bin/RANes.zip',
            // 'latest_version_url_x64' => 'bin/RANes-x64.zip',
            'name' => 'FCEUX',
            'handle' => 'RANes',
            'active' => true,
            'integration_id' => Emulators::RANes,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'systems' => [
                7, // NES, Famicom, Famicom Disk System (FDS), and Dendy
            ],
        ],
        Emulators::RANester => [
            'minimum_version' => '0',
            'latest_version' => '0',
            'latest_version_url' => 'bin/RANester.zip',
            // 'latest_version_url_x64' => 'bin/RANester-x64.zip',
            'name' => 'Nester',
            'handle' => 'RANester',
            'active' => false,
            'integration_id' => Emulators::RANester,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'systems' => [
                7, // NES
            ],
        ],
        Emulators::RAP64 => [
            'minimum_version' => '0.060',
            'latest_version' => '0.060',
            'latest_version_url' => 'bin/RAP64.zip',
            // 'latest_version_url_x64' => 'bin/RAP64-x64.zip',
            'name' => 'Project64',
            'handle' => 'RAP64',
            'active' => true,
            'integration_id' => Emulators::RAP64,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'systems' => [
                2, // Nintendo 64
            ],
        ],
        Emulators::RAPCE => [
            'minimum_version' => '0',
            'latest_version' => '0',
            'latest_version_url' => 'bin/RAPCE.zip',
            // 'latest_version_url_x64' => 'bin/RAPCE-x64.zip',
            'name' => 'PCE',
            'handle' => 'RAPCE',
            'active' => false,
            'integration_id' => Emulators::RAPCE,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'systems' => [
                8, // PC Engine/TurboGrafx
            ],
        ],
        Emulators::RAQUASI88 => [
            'minimum_version' => '1.1.3',
            'latest_version' => '1.1.3',
            'latest_version_url' => 'bin/RAQUASI88.zip',
            // 'latest_version_url_x64' => 'bin/RAQUASI88-x64.zip',
            'name' => 'Quasi88',
            'handle' => 'RAQUASI88',
            'active' => true,
            'integration_id' => Emulators::RAQUASI88,
            'link' => 'https://docs.retroachievements.org/RAQUASI88/',
            'systems' => [
                47, // PC-8000/8800
            ],
        ],
        Emulators::RASnes9x => [
            'minimum_version' => '1.0',
            'latest_version' => '1.0',
            'latest_version_url' => 'bin/RASnes9x.zip',
            // 'latest_version_url_x64' => 'bin/RASnes9x-x64.zip',
            'name' => 'Snes9x',
            'handle' => 'RASnes9x',
            'active' => true,
            'integration_id' => Emulators::RASnes9x,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'systems' => [
                3, // SNES
            ],
        ],
        Emulators::RAVBA => [
            'minimum_version' => '1.0',
            'latest_version' => '1.0',
            'latest_version_url' => 'bin/RAVBA.zip',
            // 'latest_version_url_x64' => 'bin/RAVBA-x64.zip',
            'name' => 'VisualBoyAdvance',
            'handle' => 'RAVBA',
            'active' => true,
            'integration_id' => Emulators::RAVBA,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'systems' => [
                4, // Game Boy
                5, // Game Boy Advance
                6, // Game Boy Color
            ],
        ],
        /**
         * RetroArch Supported Systems/Cores https://docs.libretro.com/guides/retroachievements/#cores-compatibility
         */
        100 => [
            'name' => 'RetroArch',
            'handle' => 'RetroArch',
            'active' => true,
            'link' => 'https://docs.retroachievements.org/FAQ/#retroarch-emulators',
            'systems' => [
                1, // Genesis/Mega Drive
                2, // Nintendo 64 (Cores: Mupen64Plus, ParaLLEl N64)
                3, // SNES (Cores: Snes9x, bsnes)
                4, // Game Boy (Cores: Gambatte, Gearboy, SameBoy)
                5, // Game Boy Advance (Cores: mGBA, VBA Next, VBA-M, Beetle GBA)
                6, // Game Boy Color (Cores: Gambatte, Gearboy, SameBoy)
                7, // NES (Cores: Mesen, FCEUmm, QuickNES)
                8, // PC Engine (Cores: Beetle PCE Fast, Beetle SGX)
                9, // Sega CD
                10, // 32X
                11, // Master System / MegaDrive - Genesis (Cores: Gearsystem, Genesis Plus GX, Picodrive)
                12, // PlayStation
                13, // Atari Lynx (Cores: Handy, Beetle Handy)
                14, // Neo Geo Pocket (Cores: Beetle NeoPop)
                15, // Game Gear
                17, // Atari Jaguar
                18, // Nintendo DS
                27, // Arcade (Cores: FB Alpha)
                24, // Pokemon Mini
                25, // Atari 2600 (Cores: Stella)
                28, // Virtual Boy (Cores: Beetle VB)
                33, // SG-1000
                39, // Saturn
                44, // ColecoVision
                51, // Atari 7800
                53, // WonderSwan
            ],
            'description' => 'Maintained by <a href="https://github.com/libretro/" target="_blank">libretro</a>. Supports a multitude of platforms - including Linux, Mac, Windows, Android.
Download from <a href="https://retroarch.com">retroarch.com</a>. See <a href="https://docs.libretro.com/guides/retroachievements/#cores-compatibility" target="_blank">Cores Compatibility List</a>.',
        ],
    ],
];
