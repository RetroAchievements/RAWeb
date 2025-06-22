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
        private readonly ?string $expectedVersionHash = null,
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
        if ($this->batch()?->cancelled()) {
            return;
        }

        // Check if the achievement set has changed since this job was queued.
        // If it has, we'll skip processing the job.
        if ($this->expectedVersionHash !== null) {
            $currentHash = Game::where('id', $this->gameId)
                ->value('achievement_set_version_hash');

            if ($currentHash !== $this->expectedVersionHash) {
                // Achievement set has changed, cancel this batch.
                $this->batch()?->cancel();

                return;
            }
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
