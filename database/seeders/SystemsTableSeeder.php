<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\System;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class SystemsTableSeeder extends Seeder
{
    public function run(): void
    {
        $activeSystems = [
            1,  // MegaDrive
            2,  // N64
            3,  // SNES
            4,  // GameBoy
            5,  // GameBoy Advance
            6,  // GameBoy Color
            7,  // NES
            8,  // PC-Engine
            12, // PSX
            13, // Atari Lynx
            14, // NeoGeo Pocket
            15, // GameGear
            16, // GameCube
            21, // PS2
            25, // Atari 2600
            41, // PSP
            45, // Intellivision
            57, // Channel F
        ];

        /*
         * System IDs: https://github.com/RetroAchievements/RAIntegration/blob/master/src/RA_Interface.h
         */
        (new Collection(config('systems')))->each(function ($systemData, $systemId) use ($activeSystems) {
            $systemData['active'] = in_array($systemId, $activeSystems);
            System::updateOrCreate(['id' => $systemId], $systemData);
        });
    }
}
