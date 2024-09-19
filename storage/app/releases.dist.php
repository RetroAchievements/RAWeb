<?php

use App\Platform\Enums\Emulators;

// TODO: replace with systems, integration release, and emulator release management
return [
    'integration' => [
        'minimum_version' => '1.0.4',
        'latest_version' => '1.2.0',
        'latest_version_url' => 'bin/RA_Integration.dll',
        'latest_version_url_x64' => 'bin/RA_Integration-x64.dll',
    ],
    'emulators' => [
        /**
         * RetroArch Supported Systems/Cores https://docs.libretro.com/guides/retroachievements/#cores-compatibility
         */
        Emulators::RetroArch => [
            'name' => 'RetroArch',
            'handle' => 'RetroArch',
            'active' => true,
            'link' => 'https://docs.libretro.com/guides/retroachievements/',
            'description' => 'Multi-system emulator that runs on many platforms including Linux, Mac, Windows, and Android.',
            'download_url' => 'https://www.retroarch.com/index.php?page=platforms',
            'source' => 'https://github.com/libretro/RetroArch',
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
                23, // Magnavox Odyssey 2
                24, // Pokemon Mini
                25, // Atari 2600 (Cores: Stella)
                27, // Arcade (Cores: FB Alpha)
                28, // Virtual Boy (Cores: Beetle VB)
                29, // MSX
                33, // SG-1000
                37, // Amstrad CPC
                39, // Saturn
                40, // Dreamcast
                41, // PlayStation Portable
                43, // 3DO
                44, // ColecoVision
                45, // Intellivision
                46, // Vectrex
                49, // PC-FX
                51, // Atari 7800
                53, // WonderSwan
                56, // Neo Geo CD
                57, // Fairchild Channel-F
                63, // Watara Supervision
                69, // Mega Duck
                71, // Arduboy
                72, // WASM-4
                76, // PC Engine CD
                80, // Uzebox
            ],
        ],
        Emulators::RALibretro => [
            'minimum_version' => '1.3.9',
            'latest_version' => '1.6.0',
            'latest_version_url' => 'bin/RALibretro.zip',
            'latest_version_url_x64' => 'bin/RALibretro-x64.zip',
            'name' => 'RALibRetro',
            'handle' => 'RALibRetro',
            'active' => true,
            'integration_id' => Emulators::RALibretro,
            'link' => 'https://docs.retroachievements.org/RALibretro/',
            'source' => 'https://github.com/RetroAchievements/RALibretro',
            'description' => 'Multi-system emulator that can be used for achievements development.',
            'systems' => [
                1, // Genesis/Mega Drive
                2, // Nintendo 64
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
                23, // Magnavox Odyssey 2
                24, // Pokemon Mini
                25, // Atari 2600 (Cores: Stella)
                27, // Arcade (Cores: FB Alpha)
                28, // Virtual Boy (Cores: Beetle VB)
                29, // MSX
                33, // SG-1000
                37, // Amstrad CPC
                39, // Saturn
                40, // Dreamcast
                41, // PlayStation Portable
                43, // 3DO
                44, // ColecoVision
                45, // Intellivision
                46, // Vectrex
                49, // PC-FX
                51, // Atari 7800
                53, // WonderSwan
                56, // Neo Geo CD
                57, // Fairchild Channel-F
                63, // Watara Supervision
                69, // Mega Duck
                71, // Arduboy
                72, // WASM-4
                76, // PC Engine CD
                80, // Uzebox
            ],
        ],
        Emulators::Bizhawk => [
            'name' => 'Bizhawk',
            'handle' => 'Bizhawk',
            'active' => true,
            'link' => 'https://tasvideos.org/Bizhawk/FAQ',
            'download_url' => 'https://tasvideos.org/BizHawk/ReleaseHistory',
            'systems' => [
                1, // Genesis/Mega Drive
                2, // Nintendo 64
                3, // SNES
                4, // Game Boy
                5, // Game Boy Advance
                6, // Game Boy Color
                7, // NES
                8, // PC Engine
                9, // Sega CD
                10, // 32X
                11, // Master System
                12, // PlayStation
                13, // Atari Lynx
                14, // Neo Geo Pocket
                15, // Game Gear
                17, // Atari Jaguar
                18, // Nintendo DS
                23, // Magnavox Odyssey 2
                25, // Atari 2600
                28, // Virtual Boy
                29, // MSX
                33, // SG-1000
                38, // Apple II
                39, // Saturn
                44, // ColecoVision
                45, // IntelliVision
                46, // Vectrex
                49, // PC-FX
                51, // Atari 7800
                53, // WonderSwan
                76, // PC Engine CD
                77, // Atari Jaguar CD
                78, // Nintendo DSi
                80, // Uzebox
            ],
        ],
        Emulators::PCSX2 => [
            'name' => 'PCSX2',
            'handle' => 'PCSX2',
            'active' => true,
            'link' => 'https://pcsx2.net/docs/category/setup/',
            'description' => 'Only emulator available supporting achievements for PlayStation 2.',
            'download_url' => 'https://pcsx2.net/downloads',
            'systems' => [
                21, // PlayStation 2
            ],
        ],
        Emulators::DuckStation => [
            'name' => 'DuckStation',
            'handle' => 'DuckStation',
            'active' => true,
            'link' => 'https://github.com/stenzek/duckstation/wiki',
            'download_url' => 'https://duckstation.org',
            'systems' => [
                12, // PlayStation
            ],
        ],
        Emulators::Dolphin => [
            'name' => 'Dolphin',
            'handle' => 'Dolphin',
            'active' => true,
            'minimum_version' => '2407-68',
            'link' => 'https://wiki.dolphin-emu.org/index.php?title=Main_Page',
            'download_url' => 'https://dolphin-emu.org/download/',
            'systems' => [
                16, // GameCube
            ],
        ],
        Emulators::PPSSPP => [
            'minimum_version' => '1.16.0',
            'name' => 'PPSSPP',
            'handle' => 'PPSSPP',
            'active' => true,
            'link' => 'https://www.ppsspp.org/docs/intro',
            'download_url' => 'https://www.ppsspp.org/download/',
            'systems' => [
                41, // PlayStation Portable
            ],
        ],
        Emulators::WinArcadia => [
            'minimum_version' => '29.41',
            'name' => 'WinArcadia',
            'handle' => 'WinArcadia',
            'active' => true,
            'link' => 'https://amigan.1emu.net/releases/',
            'download_url' => 'https://amigan.1emu.net/releases/WinArcadia-bin.rar',
            'systems' => [
                73, // Arcadia 2001
                74, // Interton VC 4000
                75, // Elektor TV Games Computer
            ],
        ],
        Emulators::RAppleWin => [
            'minimum_version' => '1.1.1',
            'latest_version' => '1.3.0',
            'latest_version_url' => 'bin/RAppleWin.zip',
            // 'latest_version_url_x64' => 'bin/RAppleWin-x64.zip',
            'name' => 'AppleWin',
            'handle' => 'RAppleWin',
            'active' => true,
            'integration_id' => Emulators::RAppleWin,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'source' => 'https://github.com/RetroAchievements/AppleWin',
            'description' => 'Only emulator available supporting achievements for Apple II.',
            'systems' => [
                38, // Apple II
            ],
        ],
        Emulators::RAQUASI88 => [
            'minimum_version' => '1.1.3',
            'latest_version' => '1.2.0',
            'latest_version_url' => 'bin/RAQUASI88.zip',
            // 'latest_version_url_x64' => 'bin/RAQUASI88-x64.zip',
            'name' => 'Quasi88',
            'handle' => 'RAQUASI88',
            'active' => true,
            'integration_id' => Emulators::RAQUASI88,
            'link' => 'https://docs.retroachievements.org/RAQUASI88/',
            'source' => 'https://github.com/RetroAchievements/quasi88',
            'systems' => [
                47, // PC-8000/8800
            ],
        ],
        Emulators::RAGens => [
            'minimum_version' => '0.058',
            'latest_version' => '0.058',
            'latest_version_url' => 'bin/RAGens.zip',
            // 'latest_version_url_x64' => 'bin/RAGens-x64.zip',
            'name' => 'Gens',
            'handle' => 'RAGens',
            'active' => false,
            'integration_id' => Emulators::RAGens,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'systems' => [
                1, // Genesis/Mega Drive
            ],
        ],
        Emulators::RAMeka => [
            'minimum_version' => '1.0',
            'latest_version' => '1.0',
            'latest_version_url' => 'bin/RAMeka.zip',
            // 'latest_version_url_x64' => 'bin/RAMeka-x64.zip',
            'name' => 'Meka',
            'handle' => 'RAMeka',
            'active' => true,
            'integration_id' => Emulators::RAMeka,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'source' => 'https://github.com/RetroAchievements/RAMeka',
            'systems' => [
                11, // Master System
                15, // Game Gear
                33, // SG-1000
                44, // ColecoVision
            ],
        ],
        Emulators::RANes => [
            'minimum_version' => '1.1',
            'latest_version' => '1.1',
            'latest_version_url' => 'bin/RANes.zip',
            'latest_version_url_x64' => 'bin/RANes-x64.zip',
            'name' => 'FCEUX',
            'handle' => 'RANes',
            'active' => true,
            'integration_id' => Emulators::RANes,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'source' => 'https://github.com/RetroAchievements/RANes',
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
            'latest_version' => '1.0',
            'latest_version_url' => 'bin/RAP64.zip',
            // 'latest_version_url_x64' => 'bin/RAP64-x64.zip',
            'name' => 'Project64',
            'handle' => 'RAP64',
            'active' => true,
            'integration_id' => Emulators::RAP64,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'source' => 'https://github.com/RetroAchievements/RAProject64',
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
        Emulators::RASnes9x => [
            'minimum_version' => '1.1',
            'latest_version' => '1.1',
            'latest_version_url' => 'bin/RASnes9x.zip',
            'latest_version_url_x64' => 'bin/RASnes9x-x64.zip',
            'name' => 'Snes9x',
            'handle' => 'RASnes9x',
            'active' => true,
            'integration_id' => Emulators::RASnes9x,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'source' => 'https://github.com/RetroAchievements/RASnes9x',
            'systems' => [
                3, // SNES
            ],
        ],
        Emulators::RAVBA => [
            'minimum_version' => '1.0.1',
            'latest_version' => '1.0.2',
            'latest_version_url' => 'bin/RAVBA.zip',
            'latest_version_url_x64' => 'bin/RAVBA-x64.zip',
            'name' => 'VisualBoyAdvance',
            'handle' => 'RAVBA',
            'active' => true,
            'integration_id' => Emulators::RAVBA,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'source' => 'https://github.com/RetroAchievements/RAVBA',
            'systems' => [
                4, // Game Boy
                5, // Game Boy Advance
                6, // Game Boy Color
            ],
        ],
    ],
];
