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
