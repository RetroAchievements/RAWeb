<?php

function static_addnewachievement($id): void
{
    sanitize_sql_inputs($id);

    $query = "UPDATE StaticData AS sd ";
    $query .= "SET sd.NumAchievements=sd.NumAchievements+1, sd.LastCreatedAchievementID='$id'";
    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();
    }
}

function static_addnewgame($id): void
{
    sanitize_sql_inputs($id);

    // Subquery to get # of games that have achievements
    $query = "UPDATE StaticData AS sd ";
    $query .= "SET sd.NumGames = (SELECT COUNT(DISTINCT ach.GameID) ";
    $query .= "                   FROM GameData gd ";
    $query .= "                   INNER JOIN Achievements ach ON ach.GameID = gd.ID), sd.LastCreatedGameID = '$id'";
    // $query = "UPDATE StaticData AS sd ";
    // $query .= "SET sd.NumGames = sd.NumGames+1, sd.LastCreatedGameID = '$id'";
    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();
    }
}

function static_addnewregistereduser($user): void
{
    sanitize_sql_inputs($user);

    $query = "UPDATE StaticData AS sd ";
    $query .= "SET sd.NumRegisteredUsers = sd.NumRegisteredUsers+1, sd.LastRegisteredUser = '$user', sd.LastRegisteredUserAt = NOW()";
    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();
    }
}

function static_setlastearnedachievement($id, $user, $points): void
{
    sanitize_sql_inputs($id, $user, $points);

    $query = "UPDATE StaticData AS sd ";
    $query .= "SET sd.NumAwarded = sd.NumAwarded+1, sd.LastAchievementEarnedID = '$id', sd.LastAchievementEarnedByUser = '$user', sd.LastAchievementEarnedAt = NOW(), sd.TotalPointsEarned=sd.TotalPointsEarned+$points";
    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();
    }
}

function static_setlastupdatedgame($id): void
{
    sanitize_sql_inputs($id);

    $query = "UPDATE StaticData AS sd ";
    $query .= "SET sd.LastUpdatedGameID = '$id'";
    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();
    }
}

function static_setlastupdatedachievement($id): void
{
    sanitize_sql_inputs($id);

    $query = "UPDATE StaticData AS sd ";
    $query .= "SET sd.LastUpdatedAchievementID = '$id'";
    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();
    }
}

function static_setnextgametoscan($gameID): void
{
    sanitize_sql_inputs($gameID);

    $query = "UPDATE StaticData AS sd
              SET sd.NextGameToScan = '$gameID'";
    s_mysql_query($query);
}

function static_setnextusertoscan($userID): void
{
    sanitize_sql_inputs($userID);

    $query = "UPDATE StaticData AS sd
              SET sd.NextUserIDToScan = '$userID'";
    s_mysql_query($query);
}

function getStaticData(): ?array
{
    $query = "SELECT sd.*, ach.Title AS LastAchievementEarnedTitle, gd.Title AS NextGameTitleToScan, gd.ImageIcon AS NextGameToScanIcon, c.Name AS NextGameToScanConsole, ua.User AS NextUserToScan
              FROM StaticData AS sd
              LEFT JOIN Achievements AS ach ON ach.ID = sd.LastAchievementEarnedID
              LEFT JOIN GameData AS gd ON gd.ID = sd.NextGameToScan
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              LEFT JOIN UserAccounts AS ua ON ua.ID = sd.NextUserIDToScan ";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();

        return null;
    }

    return mysqli_fetch_assoc($dbResult);
}
