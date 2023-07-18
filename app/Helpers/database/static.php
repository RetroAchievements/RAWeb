<?php

use Carbon\Carbon;

function static_addnewachievement(int $id): void
{
    $query = "UPDATE StaticData AS sd ";
    $query .= "SET sd.NumAchievements=sd.NumAchievements+1, sd.LastCreatedAchievementID='$id'";
    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();
    }
}

function static_addnewgame(int $id): void
{
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

function static_addnewregistereduser(string $user): void
{
    sanitize_sql_inputs($user);

    $query = "UPDATE StaticData AS sd ";
    $query .= "SET sd.NumRegisteredUsers = sd.NumRegisteredUsers+1, sd.LastRegisteredUser = '$user', sd.LastRegisteredUserAt = NOW()";
    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();
    }
}

function static_setlastearnedachievement(int $id, string $user, int $points): void
{
    $query = "UPDATE StaticData
              SET NumAwarded = NumAwarded+1,
                  LastAchievementEarnedID = $id,
                  LastAchievementEarnedByUser = :user,
                  LastAchievementEarnedAt = :now,
                  TotalPointsEarned = TotalPointsEarned+$points";
    $dbResult = legacyDbStatement($query, ['user' => $user, 'now' => Carbon::now()]);
    if (!$dbResult) {
        log_sql_fail();
    }
}

function static_setlastupdatedgame(int $id): void
{
    $query = "UPDATE StaticData AS sd ";
    $query .= "SET sd.LastUpdatedGameID = '$id'";
    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();
    }
}

function static_setlastupdatedachievement(int $id): void
{
    $query = "UPDATE StaticData AS sd ";
    $query .= "SET sd.LastUpdatedAchievementID = '$id'";
    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();
    }
}
