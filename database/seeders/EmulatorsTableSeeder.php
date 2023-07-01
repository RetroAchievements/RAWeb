<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Platform\Models\Emulator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

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
        (new Collection(getReleasesFromFile()))->each(function ($data) {
            $systems = $data['systems'] ?? [];
            unset($data['systems']);
            $emulator = Emulator::create($data);
            $emulator->systems()->sync($systems);
        });
    }
}
