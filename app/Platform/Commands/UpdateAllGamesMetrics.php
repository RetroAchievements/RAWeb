<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Models\Game;
use Illuminate\Console\Command;

class UpdateAllGamesMetrics extends Command
{
    protected $signature = 'ra:platform:game:update-all-metrics';
    protected $description = "Batch update all games' metrics";

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        /*
         * TODO: queue artisan commands instead of calling it directly
         */
        // Game::withCount('achievements')
        //     ->having('achievements_count', '>', '0')
        //     ->chunk(100, function ($games, $index) {
        //         $this->info('chunk ' . ($index * 100) . ' ' . memory_get_usage());
        //         foreach ($games as $game) {
        //             $game->updateMetrics();
        //         }
        //     });
    }
}
