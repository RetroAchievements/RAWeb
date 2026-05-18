<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\GameAchievementSet;
use App\Support\Cache\GameParentCacheInvalidator;

// TODO this was added in a hotfix - long-term we should denormalize this value!!

/**
 * Best-effort safety net for Game::parentGameId cache invalidation. Pivot
 * operations like attach()/updateExistingPivot()/detach() don't reliably fire
 * Eloquent model events, so explicit invalidation at known mutation sites is
 * the primary mechanism; this observer just covers direct create/save/delete
 * paths against GameAchievementSet.
 */
class GameAchievementSetObserver
{
    public function created(GameAchievementSet $model): void
    {
        GameParentCacheInvalidator::invalidate($model->game_id, $model->achievement_set_id);
    }

    public function updated(GameAchievementSet $model): void
    {
        // If achievement_set_id or game_id changed, the original side could still hold a stale
        // result for the *other* game in the old relationship, so flush both old and new.
        $originalGameId = $model->getOriginal('game_id');
        $originalAchievementSetId = $model->getOriginal('achievement_set_id');

        GameParentCacheInvalidator::invalidate(
            $originalGameId !== null ? (int) $originalGameId : null,
            $originalAchievementSetId !== null ? (int) $originalAchievementSetId : null,
        );

        GameParentCacheInvalidator::invalidate($model->game_id, $model->achievement_set_id);
    }

    public function deleted(GameAchievementSet $model): void
    {
        GameParentCacheInvalidator::invalidate($model->game_id, $model->achievement_set_id);
    }
}
