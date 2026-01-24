<?php

use Carbon\Carbon;

/**
 * @deprecated
 */
function static_addnewachievement(int $id): void
{
    $query = "UPDATE StaticData ";
    $query .= "SET NumAchievements=NumAchievements+1, LastCreatedAchievementID=$id";
    legacyDbStatement($query);
}

/**
 * @deprecated
 */
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

/**
 * @deprecated
 */
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

/**
 * @deprecated
 */
function static_setlastupdatedgame(int $id): void
{
    $query = "UPDATE StaticData SET LastUpdatedGameID = $id";
    legacyDbStatement($query);
}

/**
 * @deprecated
 */
function static_setlastupdatedachievement(int $id): void
{
    $query = "UPDATE StaticData SET LastUpdatedAchievementID = $id";
    legacyDbStatement($query);
}
