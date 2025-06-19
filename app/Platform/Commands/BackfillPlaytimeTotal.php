<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\PlayerGame;
use App\Platform\Jobs\UpdatePlayerGameMetricsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackfillPlaytimeTotal extends Command
{
    protected $signature = 'ra:platform:player:backfill-playtime-total
                            {count=750 : The number of playtime_total entries to backfill}';
    protected $description = 'Find player_game records without a playtime_total and calculate it for them';

    public function handle(): void
    {
        $count = (int) $this->argument('count');

        $playerGames = PlayerGame::whereNull('playtime_total')->limit($count)->get();

        $this->info('Dispatching jobs for ' . $playerGames->count() . ' player_games.');

        foreach ($playerGames as $playerGame) {
            dispatch(new UpdatePlayerGameMetricsJob($playerGame->user_id, $playerGame->game_id))
                ->onQueue('player-game-metrics');
        }

        Log::info('Queued ' . $playerGames->count() . ' playtime_total calculations.');
    }
}
