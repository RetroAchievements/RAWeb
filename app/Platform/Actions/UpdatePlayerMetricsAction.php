<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\UnlockMode;
use App\Site\Models\User;

class UpdatePlayerMetricsAction
{
    public function execute(User $user): void
    {
        $playerGames = $user->playerGames()->where('achievements_unlocked', '>', 0);
        $user->achievements_unlocked = $playerGames->sum('achievements_unlocked');
        $user->achievements_unlocked_hardcore = $playerGames->sum('achievements_unlocked_hardcore');
        $user->completion_percentage_average = $playerGames->average('completion_percentage');
        $user->completion_percentage_average_hardcore = $playerGames->average('completion_percentage_hardcore');

        // TODO refactor to use aggregated player_games metrics
        $user->RAPoints = $user->achievements()->published()->wherePivotNotNull('unlocked_hardcore_at')->sum('Points');
        $user->RASoftcorePoints = $user->achievements()->published()->wherePivotNull('unlocked_hardcore_at')->sum('Points');
        $user->TrueRAPoints = $user->achievements()->published()->wherePivotNotNull('unlocked_hardcore_at')->sum('TrueRatio');

        // TODO refactor to use the above implementation only
        // legacyDbStatement(
        //     "UPDATE UserAccounts ua
        //         LEFT JOIN (
        //             SELECT aw.User AS UserAwarded,
        //             SUM(IF(aw.HardcoreMode = " . UnlockMode::Hardcore . ", ach.Points, 0)) AS HardcorePoints,
        //             SUM(IF(aw.HardcoreMode = " . UnlockMode::Hardcore . ", ach.TrueRatio, 0)) AS TruePoints,
        //             SUM(IF(aw.HardcoreMode = " . UnlockMode::Softcore . ", ach.Points, 0)) AS TotalPoints
        //             FROM Awarded AS aw
        //             LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
        //             WHERE aw.User = :joinUsername AND ach.Flags = " . AchievementFlag::OfficialCore . "
        //         ) hc ON ua.User = hc.UserAwarded
        //         SET RAPoints = COALESCE(hc.HardcorePoints, 0),
        //             TrueRAPoints = COALESCE(hc.TruePoints, 0),
        //             RASoftcorePoints = COALESCE(hc.TotalPoints - hc.HardcorePoints, 0)
        //         WHERE User = :username
        //     ",
        //     [
        //         'joinUsername' => $user->username,
        //         'username' => $user->username,
        //     ]
        // );

        $user->save();
    }
}
