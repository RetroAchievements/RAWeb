<?php

use App\Platform\Enums\AchievementType;
use App\Platform\Enums\UnlockMode;

function getUserBestDaysList(string $user, int $offset, int $limit, int $sortBy): array
{
    sanitize_sql_inputs($user);

    $retVal = [];

    if ($sortBy < 1 || $sortBy > 13) {
        $sortBy = 1;
    }
    $orderCond = "";
    if ($sortBy == 1) {        // Date, asc
        $orderCond = "ORDER BY aw.Date DESC ";
    } elseif ($sortBy == 2) {    // Num Awarded, asc
        $orderCond = "ORDER BY NumAwarded DESC ";
    } elseif ($sortBy == 3) {    // Total Points earned, asc
        $orderCond = "ORDER BY TotalPointsEarned DESC ";
    } elseif ($sortBy == 11) {// Date, desc
        $orderCond = "ORDER BY aw.Date ASC ";
    } elseif ($sortBy == 12) {// Num Awarded, desc
        $orderCond = "ORDER BY NumAwarded ASC ";
    } elseif ($sortBy == 13) {// Total Points earned, desc
        $orderCond = "ORDER BY TotalPointsEarned ASC ";
    }

    $query = "SELECT YEAR(aw.Date) AS Year, MONTH(aw.Date) AS Month, DAY(aw.Date) AS Day, COUNT(*) AS NumAwarded, SUM(Points) AS TotalPointsEarned
                FROM Awarded AS aw
                LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                WHERE User='$user'
                AND aw.HardcoreMode = " . UnlockMode::Softcore . "
                AND ach.Flags = " . AchievementType::OfficialCore . "
                GROUP BY YEAR(aw.Date), MONTH(aw.Date), DAY(aw.Date)
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

function getAchievementsEarnedBetween(string $dateStart, string $dateEnd, ?string $username = null): array
{
    if (!isValidUsername($username)) {
        return [];
    }

    $bindings = [
        'dateStart' => $dateStart,
        'dateEnd' => $dateEnd,
        'username' => $username,
        'achievementType' => AchievementType::OfficialCore,
    ];

    $query = "SELECT aw.Date, aw.HardcoreMode,
                     ach.ID AS AchievementID, ach.Title, ach.Description,
                     ach.BadgeName, ach.Points, ach.Author,
                     gd.Title AS GameTitle, gd.ImageIcon AS GameIcon, ach.GameID,
                     c.Name AS ConsoleName
              FROM Awarded AS aw
              LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
              LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE User = :username AND ach.Flags = :achievementType
              AND Date BETWEEN :dateStart AND :dateEnd
              ORDER BY aw.Date, aw.HardcoreMode DESC
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

function getAchievementsEarnedOnDay(int $unixTimestamp, string $user): array
{
    $dateStrStart = date("Y-m-d 00:00:00", $unixTimestamp);
    $dateStrEnd = date("Y-m-d 23:59:59", $unixTimestamp);

    return getAchievementsEarnedBetween($dateStrStart, $dateStrEnd, $user);
}

function getAwardedList(
    string $user,
    ?int $offset = null,
    ?int $limit = null,
    ?string $dateFrom = null,
    ?string $dateTo = null
): array {
    sanitize_sql_inputs($user, $dateFrom, $dateTo);

    $retVal = [];

    if (!isValidUsername($user)) {
        return $retVal;
    }

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
        $dateCondition .= "AND aw.Date BETWEEN '$dateFromFormatted' AND '$dateToFormatted' ";
    }

    $query = "SELECT YEAR(aw.Date) AS Year, MONTH(aw.Date) AS Month, DAY(aw.Date) AS Day, aw.Date,
                SUM(IF(aw.HardcoreMode = " . UnlockMode::Hardcore . ", ach.Points, 0)) AS HardcorePoints,
                SUM(IF(aw.HardcoreMode = " . UnlockMode::Softcore . ", ach.Points, 0)) AS SoftcorePoints
                FROM Awarded AS aw
                LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
                WHERE aw.user = '$user'
                AND ach.Flags = " . AchievementType::OfficialCore . "

                $dateCondition
                GROUP BY YEAR(aw.Date), MONTH(aw.Date), DAY(aw.Date)
                ORDER BY aw.Date ASC
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
