<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Platform\Models\Emulator;
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
        foreach ($emulatorReleases as $integrationId => $emulatorRelease) {
            $emulator = Emulator::create([
                'integration_id' => $integrationId,
                'name' => $emulatorRelease['name'],
                'handle' => $emulatorRelease['handle'],
                'active' => $emulatorRelease['active'],
                'link' => $emulatorRelease['link'] ?? null,
                'description' => $emulatorRelease['description'] ?? null,
            ]);
            $emulator->systems()->sync($emulatorRelease['systems'] ?? []);
        }
    }
}
