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
        /*
         * System IDs: https://github.com/RetroAchievements/RAIntegration/blob/master/src/RA_Interface.h
         */
        (new Collection(config('systems')))->each(function ($systemData, $systemId) {
            unset($systemData['ID']);
            System::updateOrCreate(['id' => $systemId], $systemData);
        });
    }
}
