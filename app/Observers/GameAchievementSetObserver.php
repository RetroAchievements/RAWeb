<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\GameAchievementSet;
use App\Platform\Actions\SyncGameParentGameIdAction;

/**
 * Best-effort safety net for keeping games.parent_game_id synchronized. Pivot
 * operations like attach()/updateExistingPivot()/detach() don't reliably fire
 * Eloquent model events, so explicit synchronization at known mutation sites is
 * the primary mechanism. This observer just covers direct create/save/delete
 * paths against GameAchievementSet.
 */
class GameAchievementSetObserver
{
    public function created(GameAchievementSet $model): void
    {
        $this->syncAffected($model->game_id, $model->achievement_set_id);
    }

    public function updated(GameAchievementSet $model): void
    {
        $this->syncAffected($model->game_id, $model->achievement_set_id);

        if ($model->wasChanged(['game_id', 'achievement_set_id'])) {
            $originalGameId = $model->getOriginal('game_id');
            $originalAchievementSetId = $model->getOriginal('achievement_set_id');

            $this->syncAffected(
                $originalGameId !== null ? (int) $originalGameId : null,
                $originalAchievementSetId !== null ? (int) $originalAchievementSetId : null,
            );
        }
    }

    public function deleted(GameAchievementSet $model): void
    {
        $this->syncAffected($model->game_id, $model->achievement_set_id);
    }

    private function syncAffected(?int $gameId, ?int $achievementSetId): void
    {
        $syncAction = new SyncGameParentGameIdAction();

        foreach (GameAchievementSet::gameIdsAffectedBy($gameId, $achievementSetId) as $affectedGameId) {
            $syncAction->execute($affectedGameId);
        }
    }
}
