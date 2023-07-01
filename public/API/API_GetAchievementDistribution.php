<?php

/*
 *  API_GetAchievementDistribution - returns a mapping of the number of players who have earned each quantity of achievements for a game
 *    i : game id
 *    h : hardcore - 1 to only query hardcore unlocks, 0 to query all unlocks (default: 0)
 *    f : flags - 3 for core achievements, 5 for unofficial (default: 3)
 *
 *  map
 *   string     [key]      number of achievements earned
 *    int        [value]   number of players who have earned that many achievements
 */

use App\Platform\Enums\AchievementType;
use App\Platform\Enums\UnlockMode;

$gameID = (int) request()->query('i');
$hardcore = (int) request()->query('h', (string) UnlockMode::Softcore);
$requestedBy = request()->query('z');
$flags = (int) request()->query('f', (string) AchievementType::OfficialCore);

return response()->json(getAchievementDistribution($gameID, $hardcore, $requestedBy, $flags));
