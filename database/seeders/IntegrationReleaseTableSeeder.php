<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\IntegrationRelease;
use Illuminate\Database\Seeder;

class IntegrationReleaseTableSeeder extends Seeder
{
    public function run(): void
    {
        if (IntegrationRelease::count() > 0) {
            return;
        }

        IntegrationRelease::create([
            'version' => '1.3',
            'created_at' => '2024-04-17',
            'updated_at' => '2024-04-17',
            'stable' => 1,
            'minimum' => 1,
        ]);

        IntegrationRelease::create([
            'version' => '1.3.1',
            'created_at' => '2024-08-28',
            'updated_at' => '2024-08-28',
            'stable' => 1,
        ]);

        IntegrationRelease::create([
            'version' => '1.3.1.111',
            'created_at' => '2025-09-25',
            'updated_at' => '2025-09-25',
        ]);

        IntegrationRelease::create([
            'version' => '1.4',
            'created_at' => '2025-10-06',
            'updated_at' => '2025-10-06',
            'stable' => 1,
        ]);
    }
}
