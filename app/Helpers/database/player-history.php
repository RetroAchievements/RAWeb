<?php

use App\Models\User;
use App\Platform\Enums\AchievementFlag;

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

    $query = "SELECT DATE(pa.unlocked_at) AS Date, COUNT(*) AS NumAwarded, SUM(Points) AS TotalPointsEarned
                FROM player_achievements pa
                INNER JOIN Achievements AS ach ON ach.ID = pa.achievement_id
                WHERE pa.user_id={$user->id}
                AND ach.Flags = " . AchievementFlag::OfficialCore . "
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
        'achievementFlag' => AchievementFlag::OfficialCore,
    ];

    $query = "SELECT COALESCE(pa.unlocked_hardcore_at, pa.unlocked_at) AS Date,
                     CASE WHEN pa.unlocked_hardcore_at IS NOT NULL THEN 1 ELSE 0 END AS HardcoreMode,
                     ach.ID AS AchievementID, ach.Title, ach.Description,
                     ach.BadgeName, ach.Points, ach.TrueRatio, ach.type as Type, ach.Author,
                     gd.Title AS GameTitle, gd.ImageIcon AS GameIcon, ach.GameID,
                     c.Name AS ConsoleName
              FROM player_achievements pa
              INNER JOIN Achievements AS ach ON ach.ID = pa.achievement_id
              INNER JOIN GameData AS gd ON gd.ID = ach.GameID
              INNER JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE pa.user_id = :userid AND ach.Flags = :achievementFlag
              AND COALESCE(pa.unlocked_hardcore_at, pa.unlocked_at) BETWEEN :dateStart AND :dateEnd
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
    ?int $offset = null,
    ?int $limit = null,
    ?string $dateFrom = null,
    ?string $dateTo = null
): array {
    $retVal = [];

    $cumulHardcoreScore = 0;
    $cumulSoftcoreScore = 0;
    // if (isset($dateFrom)) {
    //     // Calculate the points value up until this point
    //     $cumulScore = getPointsAtTime( $user, $dateFrom );    // TBD!
    // }

    $limitCondition = "";
    if (isset($offset) && isset($limit)) {
        $limitCondition = "LIMIT $offset, $limit";
    }

    $dateCondition = "";
    if (isset($dateFrom) && isset($dateTo)) {
        $dateFromFormatted = $dateFrom; // 2013-07-01
        $dateToFormatted = $dateTo;
        $dateCondition .= "AND COALESCE(pa.unlocked_hardcore_at, pa.unlocked_at) BETWEEN '$dateFromFormatted' AND '$dateToFormatted' ";
    }

    $query = "SELECT DATE(COALESCE(pa.unlocked_hardcore_at, pa.unlocked_at)) AS Date,
                SUM(IF(pa.unlocked_hardcore_at IS NOT NULL, ach.Points, 0)) AS HardcorePoints,
                SUM(ach.Points) AS SoftcorePoints
                FROM player_achievements pa
                INNER JOIN Achievements AS ach ON ach.ID = pa.achievement_id
                INNER JOIN GameData AS gd ON gd.ID = ach.GameID
                WHERE pa.user_id = {$user->id}
                AND ach.Flags = " . AchievementFlag::OfficialCore . "
                $dateCondition
                GROUP BY Date
                ORDER BY Date ASC
                $limitCondition";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $daysCount = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $cumulHardcoreScore += $db_entry['HardcorePoints'];
            $cumulSoftcoreScore += (int) $db_entry['SoftcorePoints'] - (int) $db_entry['HardcorePoints'];

            $retVal[$daysCount] = $db_entry;
            $retVal[$daysCount]['CumulHardcoreScore'] = $cumulHardcoreScore;
            $retVal[$daysCount]['CumulSoftcoreScore'] = $cumulSoftcoreScore;

            $daysCount++;
        }
    } else {
        log_sql_fail();
    }

    return $retVal;
}
