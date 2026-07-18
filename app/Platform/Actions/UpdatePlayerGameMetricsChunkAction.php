<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Services\PlayerGameActivityService;
use Throwable;

class UpdatePlayerGameMetricsChunkAction
{
    /**
     * @param  list<int>  $userIds
     */
    public function execute(int $gameId, array $userIds): void
    {
        foreach ($userIds as $userId) {
            try {
                $this->processPlayer($userId, $gameId);
            } catch (Throwable $e) {
                // If there's one transient failure in the chunk (ie: DB deadlock, etc),
                // don't fail the entire chunk. Catch the transient failure and keep going.
                report($e);
            }
        }
    }

    private function processPlayer(int $userId, int $gameId): void
    {
        $playerGame = PlayerGame::where('user_id', '=', $userId)
            ->where('game_id', '=', $gameId)
            ->with(['user', 'game.system'])
            ->first();

        if (!$playerGame) {
            return;
        }

        if ($this->tryPlaytimeOnlySync($playerGame, $gameId)) {
            return;
        }

        app()->make(UpdatePlayerGameMetricsAction::class)
            ->execute($playerGame, silent: true);

        $user = User::find($userId);
        if ($user) {
            app()->make(UpdatePlayerMetricsAction::class)->execute($user);
        }
    }

    /**
     * Skip the heavy recompute path for users with no unlocks at all. Only their
     * playtime can have changed, so we sync that inline and bail. Returns true if
     * the fast path applied.
     */
    private function tryPlaytimeOnlySync(PlayerGame $playerGame, int $gameId): bool
    {
        if ($playerGame->achievements_unlocked !== 0) {
            return false;
        }

        $hasAnyUnlocks = $playerGame->user->playerAchievements()
            ->whereHas('achievement', fn ($query) => $query->where('game_id', $gameId))
            ->exists();

        if ($hasAnyUnlocks) {
            return false;
        }

        $activity = new PlayerGameActivityService();
        $activity->initialize($playerGame->user, $playerGame->game);
        $summary = $activity->summarize();

        if ($playerGame->playtime_total !== $summary['totalPlaytime']) {
            $playerGame->playtime_total = $summary['totalPlaytime'];
            $playerGame->save();
        }

        return true;
    }
}
