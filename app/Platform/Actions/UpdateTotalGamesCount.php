<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\StaticData;
use App\Models\System;
use App\Platform\Enums\AchievementFlag;

class UpdateTotalGamesCount
{
    public function execute(): void
    {
        // A game is counted if:
        // - It's not an event or a hub.
        // - It's not a subset.
        // - It has at least 6 core achievements.

        $gameCount = Game::whereNotIn("ConsoleID", System::getNonGameSystems())
            ->where("Title", "not like", "%[Subset%") // TODO this can probably be removed after multiset
            ->whereHas("achievements", function ($query) {
                $query->where("Flags", AchievementFlag::OfficialCore);
            }, ">=", 6)
            ->count();

        // TODO put this in redis or somewhere else
        StaticData::query()->update([
            'NumGames' => $gameCount,
        ]);
    }
}
