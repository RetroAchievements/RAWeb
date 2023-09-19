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

    public function uniqueId(): string
    {
        return $this->userId . '-' . $this->gameId;
    }

    public function handle(): void
    {
        app()->make(UpdatePlayerGameMetrics::class)
            ->execute(
                PlayerGame::where('user_id', '=', $this->userId)
                    ->where('game_id', '=', $this->gameId)
                    ->firstOrFail()
            );
    }
}
