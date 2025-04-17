<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameAchievementSet;

class SyncAchievementSetImageAssetPathFromGameAction
{
    public function execute(Game $game): void
    {
        $coreAchievementSet = GameAchievementSet::query()
            ->whereGameId($game->id)
            ->core()
            ->first()
            ?->achievementSet;

        if (!$coreAchievementSet) {
            return;
        }

        $coreAchievementSet->image_asset_path = $game->ImageIcon;
        $coreAchievementSet->timestamps = false;
        $coreAchievementSet->save();
    }
}
