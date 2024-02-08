<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\PlayerGame;
use App\Platform\Events\GamePlayerGameMetricsUpdated;
use App\Platform\Jobs\UpdatePlayerGameMetricsJob;
use Illuminate\Bus\Batch;
use Illuminate\Bus\BatchRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;

class UpdateGamePlayerGames
{
    public function execute(Game $game): void
    {
        // don't do this without a proper queue (unless testing)
        if (config('queue.default') === 'sync' && !app()->environment('testing')) {
            return;
        }

        // Ad-hoc updates for player games metrics and player metrics after achievement set version changes
        // Note: this might dispatch multiple thousands of jobs depending on a game's players count
        // add all affected player games to the update queue in batches
        $game->playerGames()
            ->where(function ($query) use ($game) {
                $query->whereNot('achievement_set_version_hash', '=', $game->achievement_set_version_hash)
                    ->orWhereNull('achievement_set_version_hash');
            })
            ->chunkById(1000, function (Collection $chunk, $page) use ($game) {
                // map and dispatch this chunk as a batch of jobs
                Bus::batch(
                    $chunk->map(
                        fn (PlayerGame $playerGame) => new UpdatePlayerGameMetricsJob($playerGame->user_id, $playerGame->game_id)
                    )
                )
                    ->onQueue('player-game-metrics-batch')
                    ->name('player-game-metrics ' . $game->id . ' ' . $page)
                    ->allowFailures()
                    ->finally(function (Batch $batch) use ($game) {
                        // mark batch as finished even if jobs failed
                        if (!$batch->finished()) {
                            resolve(BatchRepository::class)->markAsFinished($batch->id);
                        }
                        // some game metrics depend on the player_games rows
                        GamePlayerGameMetricsUpdated::dispatch($game);
                    })
                    ->dispatch();
            });
    }
}
