<?php

namespace Database\Seeders;

use App\Platform\Models\Badge;
use Illuminate\Database\Seeder;

class BadgesTableSeeder extends Seeder
{
    public function run()
    {
        /*
         * TODO: create an award set for on-site awards
         */
        Badge::create([
        ]);
    }
}
