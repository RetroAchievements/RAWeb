<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Platform\Events\GamePlayerGameMetricsUpdated;
use App\Platform\Jobs\UpdatePlayerGameMetricsChunkJob;
use Illuminate\Bus\Batch;
use Illuminate\Bus\BatchRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;

class UpdateGamePlayerGamesAction
{
    private const PLAYER_GAME_CHUNK_SIZE = 250;

    public function execute(Game $game): void
    {
        $versionHash = $game->achievement_set_version_hash;

        $jobs = [];
        $game->playerGames()
            ->select(['id', 'user_id'])
            ->chunkById(self::PLAYER_GAME_CHUNK_SIZE, function (Collection $chunk) use ($game, $versionHash, &$jobs): void {
                $jobs[] = new UpdatePlayerGameMetricsChunkJob(
                    $game->id,
                    $versionHash,
                    $chunk->pluck('user_id')->all(),
                );
            });

        // If a game has no player_games rows for some reason,
        // fire the listener cascade and bail.
        if ($jobs === []) {
            GamePlayerGameMetricsUpdated::dispatch($game);

            return;
        }

        Bus::batch($jobs)
            ->onQueue('player-game-metrics-batch')
            ->name('player-game-metrics ' . $game->id)
            ->allowFailures()
            ->finally(function (Batch $batch) use ($game, $versionHash): void {
                if (!$batch->finished()) {
                    resolve(BatchRepository::class)->markAsFinished($batch->id);
                }

                if ($batch->cancelled()) {
                    return;
                }

                // If the hash changes mid-flight, bail. A fresher run will clean things up.
                $currentHash = $game->newQuery()
                    ->whereKey($game->id)
                    ->value('achievement_set_version_hash');
                if ($currentHash !== $versionHash) {
                    return;
                }

                GamePlayerGameMetricsUpdated::dispatch($game);
            })
            ->dispatch();
    }
}
