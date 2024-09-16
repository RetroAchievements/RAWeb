<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Emulator;
use App\Platform\Enums\Emulators;
use Illuminate\Database\Seeder;

class EmulatorsTableSeeder extends Seeder
{
    public function run(): void
    {
        if (Emulator::count() > 0) {
            return;
        }

        /*
         * Integration IDs: https://github.com/RetroAchievements/RAIntegration/blob/master/src/RA_Interface.h
         */
        $emulatorReleases = getReleasesFromFile()['emulators'] ?? [];

        // attempt to insert the emulators such that any with numeric keys get assigned those IDs and everything
        // else is roughly in the order they were supported.
        $emulatorMap = [
            Emulators::RAP64,
            Emulators::RASnes9x,
            Emulators::RAVBA,
            Emulators::RANester,
            Emulators::RANes,
            Emulators::RAPCE,
            Emulators::RALibretro,
            Emulators::RAMeka,
            Emulators::RAQUASI88,
            Emulators::RAppleWin,
            Emulators::RAGens,
            Emulators::RetroArch,
            Emulators::PCSX2,
            Emulators::Bizhawk,
            Emulators::DuckStation,
            Emulators::WinArcadia,
            Emulators::PPSSPP,
            Emulators::Dolphin,
        ];
        foreach ($emulatorMap as $integrationId) {
            if (!array_key_exists($integrationId, $emulatorReleases)) {
                continue;
            }
            $emulatorRelease = $emulatorReleases[$integrationId];
            $emulator = Emulator::create([
                'original_name' => $emulatorRelease['name'],
                'name' => $emulatorRelease['handle'],
                'active' => $emulatorRelease['active'],
                'description' => $emulatorRelease['description'] ?? null,
                'documentation_url' => $emulatorRelease['link'] ?? null,
                'download_url' => $emulatorRelease['download_url'] ?? $emulatorRelease['latest_version_url'] ?? null,
                'download_x64_url' => $emulatorRelease['latest_version_url_x64'] ?? null,
                'source_url' => $emulatorRelease['source'] ?? null,
            ]);
            $emulator->systems()->sync($emulatorRelease['systems'] ?? []);
        }
    }
}
