<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Platform\Models\System;
use Illuminate\Database\Seeder;

class SystemsTableSeeder extends Seeder
{
    public function run()
    {
        if (System::count() > 0) {
            return;
        }

        /*
         * System IDs: https://github.com/RetroAchievements/RAIntegration/blob/master/src/RA_Interface.h
         */
        collect(config('systems'))->each(function ($systemData, $systemId) {
            $systemData['id'] = $systemId;
            System::create($systemData);
        });
    }
}
