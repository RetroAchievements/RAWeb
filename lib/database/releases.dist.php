<?php
return [
    'integration' => [
        'minimum_version' => '0',
        'latest_version' => '0',
        'latest_version_url' => 'bin/RA_Integration.dll',
        'latest_version_url_x64' => 'bin/RA_Integration-x64.dll',
    ],
    'emulators' => [
        \RA\Emulators::RALibretro => [
            'minimum_version' => '0',
            'latest_version' => '0',
            'latest_version_url' => 'bin/RALibretro.zip',
            'latest_version_url_x64' => 'bin/RALibretro-x64.zip',
            'name' => 'LibRetro',
            'active' => true,
            'integration_id' => \RA\Emulators::RALibretro,
            'link' => 'https://retroachievements.github.io/RALibretro/',
            'description' => 'RALibretro is a multi-emulator that can be used to develop RetroAchievements. Find out more about RALibRetro and development of achievements in the docs.',
            'systems' => [
            ],
        ],
        \RA\Emulators::RAGens => [
            'minimum_version' => '0',
            'latest_version' => '0',
            'latest_version_url' => 'bin/RAGens.zip',
            'latest_version_url_x64' => 'bin/RAGens-x64.zip',
            'name' => 'Gens',
            'active' => true,
            'integration_id' => \RA\Emulators::RAGens,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'systems' => [
                1, // Mega Drive
            ],
        ],
        \RA\Emulators::RAP64 => [
            'minimum_version' => '0',
            'latest_version' => '0',
            'latest_version_url' => 'bin/RAP64.zip',
            'latest_version_url_x64' => 'bin/RAP64-x64.zip',
            'name' => 'Project64',
            'active' => true,
            'integration_id' => \RA\Emulators::RAP64,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'systems' => [
                2, // Nintendo 64
            ],
        ],
        \RA\Emulators::RASnes9x => [
            'minimum_version' => '0',
            'latest_version' => '0',
            'latest_version_url' => 'bin/RASnes9x.zip',
            'latest_version_url_x64' => 'bin/RASnes9x-x64.zip',
            'name' => 'Snes9x',
            'active' => true,
            'integration_id' => \RA\Emulators::RASnes9x,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'systems' => [
                3, // SNES
            ],
        ],
        \RA\Emulators::RAVBA => [
            'minimum_version' => '0',
            'latest_version' => '0',
            'latest_version_url' => 'bin/RAVBA.zip',
            'latest_version_url_x64' => 'bin/RAVBA-x64.zip',
            'name' => 'VisualBoyAdvance',
            'active' => true,
            'integration_id' => \RA\Emulators::RAVBA,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'systems' => [
                4, // Game Boy
                5, // Game Boy Advance
                6, // Game Boy Color
            ],
        ],
        \RA\Emulators::RANester => [
            'minimum_version' => '0',
            'latest_version' => '0',
            'latest_version_url' => 'bin/RANester.zip',
            'latest_version_url_x64' => 'bin/RANester-x64.zip',
            'name' => 'Nester',
            'active' => false,
            'integration_id' => \RA\Emulators::RANester,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'systems' => [
                7, // NES
            ],
        ],
        \RA\Emulators::RANes => [
            'minimum_version' => '0',
            'latest_version' => '0',
            'latest_version_url' => 'bin/RANes.zip',
            'latest_version_url_x64' => 'bin/RANes-x64.zip',
            'name' => 'FCEUX',
            'active' => true,
            'integration_id' => \RA\Emulators::RANes,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'systems' => [
                7, // NES, Famicom, Famicom Disk System (FDS), and Dendy
            ],
        ],
        \RA\Emulators::RAPCE => [
            'minimum_version' => '0',
            'latest_version' => '0',
            'latest_version_url' => 'bin/RAPCE.zip',
            'latest_version_url_x64' => 'bin/RAPCE-x64.zip',
            'name' => 'PCE',
            'active' => false,
            'integration_id' => \RA\Emulators::RAPCE,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'systems' => [
                8, // PC Engine/TurboGrafx
            ],
        ],
        \RA\Emulators::RAMeka => [
            'minimum_version' => '0',
            'latest_version' => '0',
            'latest_version_url' => 'bin/RAMeka.zip',
            'latest_version_url_x64' => 'bin/RAMeka-x64.zip',
            'name' => 'Meka',
            'active' => true,
            'integration_id' => \RA\Emulators::RAMeka,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'systems' => [
                11, // Master System
                15, // Game Gear
                44, // ColecoVision
            ],
        ],
        \RA\Emulators::RAQUASI88 => [
            'minimum_version' => '0',
            'latest_version' => '0',
            'latest_version_url' => 'bin/RAQUASI88.zip',
            'latest_version_url_x64' => 'bin/RAQUASI88-x64.zip',
            'name' => 'Quasi88',
            'active' => true,
            'integration_id' => \RA\Emulators::RAQUASI88,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'systems' => [
                47, // PC-8000/8800
            ],
        ],
        \RA\Emulators::RAppleWin => [
            'minimum_version' => '0',
            'latest_version' => '0',
            'latest_version_url' => 'bin/RAppleWin.zip',
            'latest_version_url_x64' => 'bin/RAppleWin-x64.zip',
            'name' => 'AppleWin',
            'active' => true,
            'integration_id' => \RA\Emulators::RAppleWin,
            'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
            'systems' => [
                38, // Apple II
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
                27, // Arcade (Cores: FB Alpha)
                25, // Atari 2600 (Cores: Stella)
                7, // NES (Cores: Mesen, FCEUmm, QuickNES)
                11, // Master System / MegaDrive - Genesis (Cores: Gearsystem, Genesis Plus GX, Picodrive)
                3, // SNES (Cores: Snes9x, bsnes)
                4, // Game Boy (Cores: Gambatte, Gearboy, SameBoy)
                6, // Game Boy Color (Cores: Gambatte, Gearboy, SameBoy)
                5, // Game Boy Advance (Cores: mGBA, VBA Next, VBA-M, Beetle GBA)
                8, // PC Engine (Cores: Beetle PCE Fast, Beetle SGX)
                14, // Neo Geo Pocket (Cores: Beetle NeoPop)
                2, // Nintendo 64 (Cores: Mupen64Plus, ParaLLEl N64)
                13, // Lynx (Cores: Handy, Beetle Handy)
                28, // Virtual Boy (Cores: Beetle VB)
            ],
        ],
    ],
];
