<?php

function static_addnewachievement($id)
{
    sanitize_query_inputs($id);

    $query = "UPDATE StaticData AS sd ";
    $query .= "SET sd.NumAchievements=sd.NumAchievements+1, sd.LastCreatedAchievementID='$id'";
    // log_sql($query);
    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        //    ONLY if it goes wrong, report an error.
        // error_log(__FUNCTION__);
        log_sql_fail();
    }
}

function static_addnewgame($id)
{
    sanitize_query_inputs($id);

    // Subquery to get # of games that have achievements
    $query = "UPDATE StaticData AS sd ";
    $query .= "SET sd.NumGames = (SELECT COUNT(DISTINCT ach.GameID) ";
    $query .= "                   FROM GameData gd ";
    $query .= "                   INNER JOIN Achievements ach ON ach.GameID = gd.ID), sd.LastCreatedGameID = '$id'";
    //$query = "UPDATE StaticData AS sd ";
    //$query .= "SET sd.NumGames = sd.NumGames+1, sd.LastCreatedGameID = '$id'";
    // log_sql($query);
    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        //    ONLY if it goes wrong, report an error.
        // error_log(__FUNCTION__);
        log_sql_fail();
    }
}

function static_addnewregistereduser($user)
{
    sanitize_query_inputs($user);

    $query = "UPDATE StaticData AS sd ";
    $query .= "SET sd.NumRegisteredUsers = sd.NumRegisteredUsers+1, sd.LastRegisteredUser = '$user', sd.LastRegisteredUserAt = NOW()";
    // log_sql($query);
    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        //    ONLY if it goes wrong, report an error.
        // error_log(__FUNCTION__);
        log_sql_fail();
    }
}

function static_setlastearnedachievement($id, $user, $points)
{
    sanitize_query_inputs($id, $user, $points);

    $query = "UPDATE StaticData AS sd ";
    $query .= "SET sd.NumAwarded = sd.NumAwarded+1, sd.LastAchievementEarnedID = '$id', sd.LastAchievementEarnedByUser = '$user', sd.LastAchievementEarnedAt = NOW(), sd.TotalPointsEarned=sd.TotalPointsEarned+$points";
    // log_sql($query);
    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        //    ONLY if it goes wrong, report an error.
        // error_log(__FUNCTION__);
        log_sql_fail();
    }
}

function static_setlastupdatedgame($id)
{
    sanitize_query_inputs($id);

    $query = "UPDATE StaticData AS sd ";
    $query .= "SET sd.LastUpdatedGameID = '$id'";
    // log_sql($query);
    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        //    ONLY if it goes wrong, report an error.
        // error_log(__FUNCTION__);
        log_sql_fail();
    }
}

function static_setlastupdatedachievement($id)
{
    sanitize_query_inputs($id);

    $query = "UPDATE StaticData AS sd ";
    $query .= "SET sd.LastUpdatedAchievementID = '$id'";
    // log_sql($query);
    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        //    ONLY if it goes wrong, report an error.
        // error_log(__FUNCTION__);
        log_sql_fail();
    }
}

function static_setnextgametoscan($gameID)
{
    sanitize_query_inputs($gameID);

    $query = "UPDATE StaticData AS sd
              SET sd.NextGameToScan = '$gameID'";
    $dbResult = s_mysql_query($query);

    SQL_ASSERT($dbResult);
}

function static_setnextusertoscan($userID)
{
    sanitize_query_inputs($userID);

    $query = "UPDATE StaticData AS sd
              SET sd.NextUserIDToScan = '$userID'";
    $dbResult = s_mysql_query($query);

    SQL_ASSERT($dbResult);
}

function getStaticData()
{
    $query = "SELECT sd.*, ach.Title AS LastAchievementEarnedTitle, gd.Title AS NextGameTitleToScan, gd.ImageIcon AS NextGameToScanIcon, c.Name AS NextGameToScanConsole, ua.User AS NextUserToScan
              FROM StaticData AS sd
              LEFT JOIN Achievements AS ach ON ach.ID = sd.LastAchievementEarnedID
              LEFT JOIN GameData AS gd ON gd.ID = sd.NextGameToScan
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              LEFT JOIN UserAccounts AS ua ON ua.ID = sd.NextUserIDToScan ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult);
    }

    // error_log(__FUNCTION__);
    log_sql_fail();

    return null;
}
