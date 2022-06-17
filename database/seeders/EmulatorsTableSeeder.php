<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Platform\Models\Emulator;
use Illuminate\Database\Seeder;

class EmulatorsTableSeeder extends Seeder
{
    public function run()
    {
        if (Emulator::count() > 0) {
            return;
        }

        /*
         * Integration IDs: https://github.com/RetroAchievements/RAIntegration/blob/master/src/RA_Interface.h
         */
        collect([
            /*
             * RetroArch Supported Systems/Cores https://docs.libretro.com/guides/retroachievements/#cores-compatibility
             */
            [
                'name' => 'RetroArch',
                'handle' => 'RetroArch',
                'active' => true,
                'link' => 'https://docs.retroachievements.org/FAQ/#retroarch-emulators',
                'description' => 'Maintained by <a href="https://github.com/libretro/">libretro</a>. Supports a multitude of platforms - including Linux, Mac, Windows, Android.<br>
Download from <a href="https://retroarch.com">retroarch.com</a>.<br>See <a href="https://docs.libretro.com/guides/retroachievements/#cores-compatibility">Cores Compatibility List</a>.',
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
            ],
            [
                'name' => 'LibRetro',
                'handle' => 'RALibretro',
                'integration_id' => 7,
                'active' => true,
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
            [
                'name' => 'AppleWin',
                'handle' => 'RAppleWin',
                'integration_id' => 10,
                'active' => true,
                'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
                'description' => '<b>NOTE:</b> only emulator available supporting achievements for Apple II.',
                'systems' => [
                    38, // Apple II
                ],
            ],
            [
                'name' => 'Quasi88',
                'handle' => 'RAQUASI88',
                'integration_id' => 9,
                'active' => true,
                'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
                'description' => '<b>NOTE:</b> only emulator available supporting achievements for PC-8000/8800.',
                'systems' => [
                    47, // PC-8000/8800
                ],
            ],
            [
                'name' => 'Gens',
                'handle' => 'RAGens',
                'integration_id' => 0,
                'active' => true,
                'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
                'systems' => [
                    1, // Genesis/Mega Drive
                ],
            ],
            [
                'name' => 'Meka',
                'handle' => 'RAMeka',
                'integration_id' => 8,
                'active' => true,
                'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
                'systems' => [
                    11, // Master System
                    15, // Game Gear
                    33, // SG-1000
                    44, // ColecoVision
                ],
            ],
            [
                'name' => 'FCEUX', // RA_FCEUX
                'handle' => 'RANes', // RA_Nester
                'integration_id' => 5,
                'active' => true,
                'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
                'systems' => [
                    7, // NES, Famicom, Famicom Disk System (FDS), and Dendy
                ],
            ],
            [
                'name' => 'Nester', // RA_Nester
                'handle' => 'RANester', // RA_Nester
                'integration_id' => 4,
                'active' => false,
                'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
                'systems' => [
                    7, // NES
                ],
            ],
            [
                'name' => 'Project64',
                'handle' => 'RAP64',
                'integration_id' => 1,
                'active' => true,
                'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
                'systems' => [
                    2, // Nintendo 64
                ],
            ],
            [
                'name' => 'PCE',
                'handle' => 'RAPCE',
                'integration_id' => 6,
                'active' => false,
                'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
                'systems' => [
                    8, // PC Engine/TurboGrafx
                ],
            ],
            [
                'name' => 'Snes9x',
                'handle' => 'RASnes9x',
                'integration_id' => 2,
                'active' => true,
                'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
                'systems' => [
                    3, // SNES
                ],
            ],
            [
                'name' => 'VisualBoyAdvance', // RA_VisualboyAdvance
                'handle' => 'RAVBA', // RA_VisualboyAdvance
                'integration_id' => 3,
                'active' => true,
                'link' => 'https://docs.retroachievements.org/FAQ/#official-retroachievementsorg-emulators',
                'systems' => [
                    4, // Game Boy
                    5, // Game Boy Advance
                    6, // Game Boy Color
                ],
            ],
            // [
            //     'name' => 'RA Oricutron',
            //     'active' => true,
            //     'systems' => [32],
            // ],
        ])->each(function ($data) {
            $systems = $data['systems'] ?? [];
            unset($data['systems']);
            $emulator = Emulator::create($data);
            $emulator->systems()->sync($systems);
        });
    }
}
