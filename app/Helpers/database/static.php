<?php

use App\Models\User;
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
function static_addnewhardcoremastery(int $gameId, string $username): void
{
    $foundUser = User::firstWhere('User', $username);
    if ($foundUser->Untracked) {
        return;
    }

    $query = "UPDATE StaticData
        SET
            num_hardcore_mastery_awards = num_hardcore_mastery_awards+1,
            last_game_hardcore_mastered_game_id = :gameId,
            last_game_hardcore_mastered_user_id = :userId,
            last_game_hardcore_mastered_at = :now
    ";

    legacyDbStatement($query, ['gameId' => $gameId, 'userId' => $foundUser->ID, 'now' => Carbon::now()]);
}

/**
 * @deprecated
 */
function static_addnewhardcoregamebeaten(int $gameId, string $username): void
{
    $foundUser = User::firstWhere('User', $username);
    if ($foundUser->Untracked) {
        return;
    }

    $query = "UPDATE StaticData
        SET
            num_hardcore_game_beaten_awards = num_hardcore_game_beaten_awards+1,
            last_game_hardcore_beaten_game_id = :gameId,
            last_game_hardcore_beaten_user_id = :userId,
            last_game_hardcore_beaten_at = :now
    ";

    legacyDbStatement($query, ['gameId' => $gameId, 'userId' => $foundUser->ID, 'now' => Carbon::now()]);
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
