<?php

namespace App\Platform\Jobs;

use App\Platform\Actions\UpdatePlayerGameMetrics;
use App\Platform\Models\PlayerGame;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdatePlayerGameMetricsJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $userId,
        private readonly int $gameId,
    ) {
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $playerGame = PlayerGame::where('user_id', '=', $this->userId)
            ->where('game_id', '=', $this->gameId)
            ->first();

        if (!$playerGame) {
            // might've been deleted
            return;
        }

        $silent = $this->batchId !== null;

        app()->make(UpdatePlayerGameMetrics::class)
            ->execute($playerGame, $silent);

        // if this job was executed from within a batch it means that it's been initiated
        // by a game metrics update.
        // make sure to update player metrics directly, as the silent flag will not
        // trigger an event (to not further cascade into another game metrics update).
        $this->batch()?->add(new UpdatePlayerMetricsJob($playerGame->user_id));
    }
}
