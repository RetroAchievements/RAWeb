<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Jobs\UpdateGameMetricsJob;
use App\Platform\Services\GameTopAchieversService;
use Illuminate\Bus\Batch;
use Illuminate\Bus\BatchRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;

class UpdatePlayerPlayedGames
{
    public function execute(User $user): void
    {
        // don't do this without a proper queue (unless testing)
        if (config('queue.default') === 'sync' && !app()->environment('testing')) {
            return;
        }

        $user->playerGames()
            ->chunkById(1000, function (Collection $chunk, $page) use ($user) {
                // map and dispatch this chunk as a batch of jobs
                Bus::batch(
                    $chunk->map(
                        fn (PlayerGame $playerGame) => new UpdateGameMetricsJob($playerGame->game_id)
                    )
                )
                    ->onQueue('game-metrics')
                    ->name('player-played-games ' . $user->id . ' ' . $page)
                    ->allowFailures()
                    ->finally(function (Batch $batch) {
                        // mark batch as finished even if jobs failed
                        if (!$batch->finished()) {
                            resolve(BatchRepository::class)->markAsFinished($batch->id);
                        }
                    })
                    ->dispatch();

                /** @var PlayerGame $playerGame */
                foreach ($chunk as $playerGame) {
                    GameTopAchieversService::expireTopAchieversComponentData($playerGame->game_id);
                }
            });
    }
}
