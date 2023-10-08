<?php

namespace App\Platform\Jobs;

use App\Platform\Actions\UpdatePlayerGameMetrics;
use App\Platform\Models\PlayerGame;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdatePlayerGameMetricsJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
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
        $playerGame = PlayerGame::where('user_id', '=', $this->userId)
            ->where('game_id', '=', $this->gameId)
            ->first();

        if (!$playerGame) {
            // game player might not exist anymore
            return;
        }

        app()->make(UpdatePlayerGameMetrics::class)
            ->execute($playerGame);
    }
}
