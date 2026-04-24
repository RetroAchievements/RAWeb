<?php

use App\Models\PlayerAchievement;
use App\Models\System;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

function getUserBestDaysList(User $user, int $offset, int $limit, int $sortBy): array
{
    $retVal = [];

    if ($sortBy < 1 || $sortBy > 13) {
        $sortBy = 1;
    }
    $orderCond = "";
    if ($sortBy == 1) {        // Date, asc
        $orderCond = "ORDER BY Date DESC ";
    } elseif ($sortBy == 2) {    // Num Awarded, asc
        $orderCond = "ORDER BY NumAwarded DESC ";
    } elseif ($sortBy == 3) {    // Total Points earned, asc
        $orderCond = "ORDER BY TotalPointsEarned DESC ";
    } elseif ($sortBy == 11) {// Date, desc
        $orderCond = "ORDER BY Date ASC ";
    } elseif ($sortBy == 12) {// Num Awarded, desc
        $orderCond = "ORDER BY NumAwarded ASC ";
    } elseif ($sortBy == 13) {// Total Points earned, desc
        $orderCond = "ORDER BY TotalPointsEarned ASC ";
    }

    $query = "SELECT DATE(pa.unlocked_at) AS Date, COUNT(*) AS NumAwarded, SUM(ach.points) AS TotalPointsEarned
                FROM player_achievements pa
                INNER JOIN achievements AS ach ON ach.id = pa.achievement_id
                INNER JOIN games AS gd ON gd.id = ach.game_id
                WHERE pa.user_id={$user->id}
                AND ach.is_promoted = 1
                AND gd.system_id != " . System::Events . "
                GROUP BY Date
                $orderCond
                LIMIT $offset, $limit";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $daysCount = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[$daysCount] = $db_entry;
            $daysCount++;
        }
    } else {
        log_sql_fail();
    }

    return $retVal;
}

function getAchievementsEarnedBetween(string $dateStart, string $dateEnd, User $user): array
{
    $bindings = [
        'dateStart' => $dateStart,
        'dateEnd' => $dateEnd,
        'userid' => $user->id,
        'isPromoted' => 1,
    ];

    $query = "SELECT pa.unlocked_effective_at AS Date,
                     CASE WHEN pa.unlocked_hardcore_at IS NOT NULL THEN 1 ELSE 0 END AS HardcoreMode,
                     ach.id AS AchievementID, ach.title AS Title, ach.description AS Description,
                     ach.image_name AS BadgeName, ach.points AS Points, ach.points_weighted AS TrueRatio, ach.type as Type,
                     COALESCE(ua.display_name, ua.username) AS Author, ua.ulid AS AuthorULID,
                     gd.title AS GameTitle, gd.image_icon_asset_path AS GameIcon, ach.game_id AS GameID,
                     s.name AS ConsoleName
              FROM player_achievements pa
              INNER JOIN achievements AS ach ON ach.id = pa.achievement_id
              INNER JOIN games AS gd ON gd.id = ach.game_id
              INNER JOIN systems AS s ON s.id = gd.system_id
              INNER JOIN users AS ua on ua.id = ach.user_id
              WHERE pa.user_id = :userid AND ach.is_promoted = :isPromoted
              AND pa.unlocked_effective_at BETWEEN :dateStart AND :dateEnd
              ORDER BY Date, HardcoreMode DESC
              LIMIT 500";

    $cumulativeScore = 0;

    return legacyDbFetchAll($query, $bindings)
        ->map(function ($entry) use (&$cumulativeScore) {
            $cumulativeScore += (int) $entry['Points'];
            $entry['CumulScore'] = $cumulativeScore;

            settype($entry['AchievementID'], 'integer');
            settype($entry['Points'], 'integer');
            settype($entry['HardcoreMode'], 'integer');
            settype($entry['GameID'], 'integer');

            return $entry;
        })
        ->toArray();
}

function getAchievementsEarnedOnDay(int $unixTimestamp, User $user): array
{
    $dateStrStart = date("Y-m-d 00:00:00", $unixTimestamp);
    $dateStrEnd = date("Y-m-d 23:59:59", $unixTimestamp);

    return getAchievementsEarnedBetween($dateStrStart, $dateStrEnd, $user);
}

function getAwardedList(
    User $user,
    bool $excludeEvents = true,
    ?int $offset = null,
    ?int $limit = null,
    ?string $dateFrom = null,
    ?string $dateTo = null,
): array {
    $rows = PlayerAchievement::query()
        ->join('achievements', 'achievements.id', '=', 'player_achievements.achievement_id')
        ->join('games', 'games.id', '=', 'achievements.game_id')
        ->where('player_achievements.user_id', $user->id)
        ->where('achievements.is_promoted', 1)
        ->when(
            $excludeEvents,
            fn (Builder $q) => $q->where('games.system_id', '!=', System::Events),
        )
        ->when(
            isset($dateFrom, $dateTo),
            fn (Builder $q) => $q->whereBetween(
                'player_achievements.unlocked_effective_at',
                [$dateFrom, $dateTo],
            ),
        )
        ->selectRaw('DATE(player_achievements.unlocked_effective_at) AS Date')
        ->selectRaw(
            'SUM(CASE WHEN player_achievements.unlocked_hardcore_at IS NOT NULL '
            . 'THEN achievements.points ELSE 0 END) AS HardcorePoints'
        )
        ->selectRaw('SUM(achievements.points) AS SoftcorePoints')
        ->groupBy('Date')
        ->orderBy('Date')
        ->when(
            isset($offset, $limit),
            fn (Builder $q) => $q->offset($offset)->limit($limit),
        )
        ->get();

    $cumulHardcoreScore = 0;
    $cumulSoftcoreScore = 0;

    return $rows
        ->map(function ($row) use (&$cumulHardcoreScore, &$cumulSoftcoreScore) {
            $hardcorePoints = (int) $row->HardcorePoints;
            $allPoints = (int) $row->SoftcorePoints;

            $cumulHardcoreScore += $hardcorePoints;
            $cumulSoftcoreScore += $allPoints - $hardcorePoints;

            return [
                'Date' => $row->Date,
                'HardcorePoints' => $row->HardcorePoints,
                'SoftcorePoints' => $row->SoftcorePoints,
                'CumulHardcoreScore' => $cumulHardcoreScore,
                'CumulSoftcoreScore' => $cumulSoftcoreScore,
            ];
        })
        ->values()
        ->all();
}
