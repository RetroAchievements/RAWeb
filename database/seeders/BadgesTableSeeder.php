<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Platform\Models\Badge;
use Illuminate\Database\Seeder;

class BadgesTableSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * TODO: create an award set for on-site awards
         */
        Badge::create([
        ]);
    }
}
