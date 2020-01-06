<?php
function getUserBestDaysList($user, $listOffset, $maxDays, $sortBy)
{
    $retVal = [];

    $query = "SELECT YEAR(aw.Date) AS Year, MONTH(aw.Date) AS Month, DAY(aw.Date) AS Day, COUNT(*) AS NumAwarded, SUM(Points) AS TotalPointsEarned FROM Awarded AS aw ";
    $query .= "LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID ";
    $query .= "WHERE User='$user' ";
    $query .= "GROUP BY YEAR(aw.Date), MONTH(aw.Date), DAY(aw.Date) ";

    settype($sortBy, 'integer');
    if ($sortBy < 1 || $sortBy > 13) {
        $sortBy = 1;
    }

    if ($sortBy == 1) {        //    Date, asc
        $query .= "ORDER BY aw.Date DESC ";
    } elseif ($sortBy == 2) {    //    Num Awarded, asc
        $query .= "ORDER BY NumAwarded DESC ";
    } elseif ($sortBy == 3) {    //    Total Points earned, asc
        $query .= "ORDER BY TotalPointsEarned DESC ";
    } elseif ($sortBy == 11) {//    Date, desc
        $query .= "ORDER BY aw.Date ASC ";
    } elseif ($sortBy == 12) {//    Num Awarded, desc
        $query .= "ORDER BY NumAwarded ASC ";
    } elseif ($sortBy == 13) {//    Total Points earned, desc
        $query .= "ORDER BY TotalPointsEarned ASC ";
    }

    $query .= "LIMIT $listOffset, $maxDays ";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $daysCount = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[$daysCount] = $db_entry;
            $daysCount++;
        }
    } else {
        log_sql_fail();
        //log_email(__FUNCTION__ . " issues - cannot retrieve list for this user?!");
    }

    return $retVal;
}

function getAchievementsEarnedBetween($dateStart, $dateEnd, $user)
{
    $retVal = [];

    if (!isValidUsername($user)) {
        return $retVal;
    }

    //error_log( __FUNCTION__ . " $dateStart, $dateEnd" );

    //$dateStrStart = date( "Y-m-d H:i:s", strtotime( $dateStart ) );
    //$dateStrEnd = date( "Y-m-d H:i:s", strtotime( $dateEnd ) );
    $dateStrStart = $dateStart;
    $dateStrEnd = $dateEnd;

    $query = "SELECT aw.Date, aw.HardcoreMode, ach.ID AS AchievementID, ach.Title, ach.Description, ach.BadgeName, ach.Points, ach.Author, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon, ach.GameID, c.Name AS ConsoleName
              FROM Awarded AS aw
              LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
              LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE User = '$user' AND ach.Flags = 3 AND Date BETWEEN '$dateStrStart' AND '$dateStrEnd'
              ORDER BY aw.Date
              LIMIT 500";

    //error_log( $query );

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $achCount = 0;
        $cumulScore = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $cumulScore += $db_entry['Points'];

            $retVal[$achCount] = $db_entry;
            $retVal[$achCount]['CumulScore'] = $cumulScore;

            $achCount++;
        }
    } else {
        log_sql_fail();
        //log_email(__FUNCTION__ . " issues - cannot retrieve day's ach for this user?!");
    }

    return $retVal;
}

function getAchievementsEarnedOnDay($dateInput, $user)
{
    $dateStrStart = date("Y-m-d 00:00:00", $dateInput);
    $dateStrEnd = date("Y-m-d 23:59:59", $dateInput);

    //error_log( __FUNCTION__ . " converting $dateInput to $dateStrStart and $dateStrEnd" );

    return getAchievementsEarnedBetween($dateStrStart, $dateStrEnd, $user);
}

function getAwardedList($user, $listOffset, $maxToFetch, $dateFrom = null, $dateTo = null)
{
    $retVal = [];

    if (!isValidUsername($user)) {
        return $retVal;
    }

    $cumulScore = 0;
    if (isset($dateFrom)) {
        //    Calculate the points value up until this point
        //$cumulScore = getPointsAtTime( $user, $dateFrom );    //    TBD!
    }

    $query = "SELECT YEAR(aw.Date) AS Year, MONTH(aw.Date) AS Month, DAY(aw.Date) AS Day, aw.Date, SUM(ach.Points) AS Points FROM Awarded AS aw ";
    $query .= "LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID ";
    $query .= "LEFT JOIN GameData AS gd ON gd.ID = ach.GameID ";
    $query .= "WHERE aw.user = '$user' ";    //AND ach.Flags = 3

    if (isset($dateFrom) && isset($dateTo)) {
        $dateFromFormatted = $dateFrom; //2013-07-01
        $dateToFormatted = $dateTo;
        $query .= "AND aw.Date BETWEEN '$dateFromFormatted' AND '$dateToFormatted' ";
    }

    $query .= "GROUP BY YEAR(aw.Date), MONTH(aw.Date), DAY(aw.Date) ";
    $query .= "ORDER BY aw.Date ASC ";

    // settype( $sortBy, 'integer' );
    // if( $sortBy < 1 || $sortBy > 12 )
    // $sortBy = 1;

    // if( $sortBy == 1 )        //    Date, asc
    // {
    // $query.= "ORDER BY aw.Date DESC ";
    // }
    // elseif( $sortBy == 2 )    //    Num Awarded, asc
    // {
    // $query.= "ORDER BY NumAwarded DESC ";
    // }
    // elseif( $sortBy == 11 )//    Date, desc
    // {
    // $query.= "ORDER BY aw.Date ASC ";
    // }
    // elseif( $sortBy == 12 )//    Num Awarded, desc
    // {
    // $query.= "ORDER BY NumAwarded ASC ";
    // }

    $query .= "LIMIT $listOffset, $maxToFetch ";

    //echo $query;

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $daysCount = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $cumulScore += $db_entry['Points'];

            $retVal[$daysCount] = $db_entry;
            $retVal[$daysCount]['CumulScore'] = $cumulScore;

            $daysCount++;
        }
    } else {
        log_sql_fail();
        //log_email(__FUNCTION__ . " issues - cannot retrieve list for this user?!");
    }

    return $retVal;
}
