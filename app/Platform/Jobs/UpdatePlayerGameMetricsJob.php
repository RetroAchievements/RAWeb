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
                // Achievement set has changed, skip this outdated job.
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

        // We might be able to skip a significant amount of processing if this is part of a
        // batch job and the player has never unlocked any achievements. To confirm, we need to
        // check for actual unlocks, not just the denormalized counts, because unofficial
        // achievements don't count towards achievements_unlocked until they're promoted.
        if ($isBatched && $playerGame->achievements_unlocked === 0 && $playerGame->all_achievements_unlocked === 0) {
            // Double-check if the player has ANY unlocks for this game (including unofficial).
            // If they do have any unlocks, we'll run a full metrics update.
            $hasAnyUnlocks = $playerGame->user->playerAchievements()
                ->whereHas('achievement', function ($query) {
                    $query->where('GameID', $this->gameId);
                })
                ->exists();

            // If they don't have any unlocks, we'll run a lightweight job just to calculate
            // the player's playtime, and we'll skip processing all the heavy stuff.
            if (!$hasAnyUnlocks) {
                // Always update playtime for players with no achievements.
                dispatch(new UpdatePlayerGamePlaytimeJob($this->userId, $this->gameId))
                    ->onQueue('player-game-metrics-batch');

                return;
            }
        }

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
