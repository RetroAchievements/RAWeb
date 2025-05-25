<?php

namespace App\Platform\Jobs;

use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Actions\UpdatePlayerGameMetricsAction;
use App\Platform\Actions\UpdatePlayerMetricsAction;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

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

    public int $uniqueFor = 3600;

    public function uniqueId(): string
    {
        return config('queue.default') === 'sync' ? '' : $this->userId . '-' . $this->gameId;
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            User::class . ':' . $this->userId,
            Game::class . ':' . $this->gameId,
        ];
    }

    public function handle(): void
    {
        /**
         * This action is very prone to causing CPU spikes and high
         * DB load in production if it's left to run wild on its own.
         * We'll cap the number of jobs that can be executed per second
         * to keep load at a reasonable level.
         */
        Redis::throttle('player-game-metrics')
            ->allow(30) // 30 jobs ...
            ->every(1)  // ... per second
            ->then(function () {
                $this->processJob();
            }, function () {
                $this->release(1);
            });
    }

    private function processJob(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $playerGame = PlayerGame::where('user_id', '=', $this->userId)
            ->where('game_id', '=', $this->gameId)
            ->with(['user', 'game.system'])
            ->first();

        if (!$playerGame) {
            // might've been deleted
            return;
        }

        $isBatched = $this->batchId !== null;

        app()->make(UpdatePlayerGameMetricsAction::class)
            ->execute($playerGame, silent: $isBatched);

        // if this job was executed from within a batch it means that it's been initiated
        // by a game metrics update.
        // make sure to update player metrics directly, as the silent flag will not
        // trigger an event (to not further cascade into another game metrics update).
        if ($isBatched) {
            $user = User::find($this->userId);
            if ($user) {
                app()->make(UpdatePlayerMetricsAction::class)
                    ->execute($user);
            }
        }
    }
}
