<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Platform\Models\Emulator;
use App\Platform\Models\IntegrationRelease;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ReleaseTablesSeeder extends Seeder
{
    public function run(): void
    {
        if (IntegrationRelease::count() > 0) {
            return;
        }

        IntegrationRelease::create([
            'version' => '0.78',
            'minimum' => true,
            'stable' => true,
        ]);

        // TODO (new Collection(getReleasesFromFile()))->
        (new Collection([
            [
                'handle' => 'RAGens',
                'integration_id' => 0,
                'releases' => [
                    [
                        'version' => '0.058',
                        'stable' => true,
                    ],
                ],
            ],
            [
                'handle' => 'RAP64',
                'integration_id' => 1,
                'releases' => [
                    [
                        'version' => '0.060',
                        'stable' => true,
                    ],
                ],
            ],
            [
                'handle' => 'RASnes9x',
                'integration_id' => 2,
                'releases' => [
                    [
                        'version' => '1.0',
                        'stable' => true,
                    ],
                ],
            ],
            [
                'handle' => 'RAVBA', // RA_VisualboyAdvance
                'integration_id' => 3,
                'releases' => [
                    [
                        'version' => '1.0',
                        'stable' => true,
                    ],
                ],
            ],
            [
                'handle' => 'RANester', // RA_Nester
                'integration_id' => 4,
                'releases' => [
                    [
                        // 'version' => 'x.x',
                        'stable' => true,
                    ],
                ],
            ],
            [
                'handle' => 'RANes', // RA_Nester
                'integration_id' => 5,
                'releases' => [
                    [
                        'version' => '0.017',
                        'stable' => true,
                    ],
                ],
            ],
            [
                'handle' => 'RAPCE',
                'integration_id' => 6,
                'releases' => [
                    [
                        // 'version' => 'x.x',
                        // 'stable' => true,
                    ],
                ],
            ],
            [
                'handle' => 'RALibretro',
                'integration_id' => 7,
                'releases' => [
                    [
                        'version' => '1.3',
                        'stable' => true,
                    ],
                ],
            ],
            [
                'handle' => 'RAMeka',
                'integration_id' => 8,
                'releases' => [
                    [
                        'version' => '0.023',
                        'stable' => true,
                    ],
                ],
            ],
            [
                'handle' => 'RAQUASI88',
                'integration_id' => 9,
                'releases' => [
                    [
                        'version' => '1.1.3',
                        'stable' => true,
                    ],
                ],
            ],
            [
                'handle' => 'RAppleWin',
                'integration_id' => 10,
                'releases' => [
                    [
                        'version' => '1.1.1',
                        'stable' => true,
                    ],
                ],
            ],
        ]))->each(function ($data) {
            Emulator::where('integration_id', $data['integration_id'])->first()->releases()->createMany($data['releases']);
        });
    }
}
