<?php

use App\Community\Enums\AwardType;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\TicketState;
use App\Enums\Permissions;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;

function GetUserData(string $username): ?array
{
    return User::whereName($username)->first()?->toArray();
}

function getUserIDFromUser(?string $user): int
{
    if (!$user) {
        return 0;
    }

    $userModel = User::whereName($user)->first();
    if (!$userModel) {
        return 0;
    }

    return $userModel->id;
}

function getUserMetadataFromID(int $userID): ?array
{
    $query = "SELECT * FROM users WHERE id ='$userID'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult);
    }

    return null;
}

function validateUsername(string $userIn): ?string
{
    $user = User::whereName($userIn)->first();

    return ($user !== null) ? $user->username : null;
}

/**
 * @deprecated use Eloquent ORM
 */
function getUserPageInfo(string $username, int $numGames = 0, int $numRecentAchievements = 0): array
{
    $user = User::whereName($username)->first();
    if (!$user) {
        return [];
    }

    $libraryOut = [];

    $libraryOut['User'] = $user->display_name;
    $libraryOut['MemberSince'] = $user->created_at->__toString();
    $libraryOut['LastActivity'] = $user->last_activity_at?->__toString();
    $libraryOut['RichPresenceMsg'] = empty($user->rich_presence) || $user->rich_presence === 'Unknown' ? null : $user->rich_presence;
    $libraryOut['RichPresenceMsgDate'] = $user->rich_presence_updated_at?->__toString();
    $libraryOut['LastGameID'] = (int) $user->last_game_id;
    $libraryOut['ContribCount'] = (int) $user->yield_unlocks;
    $libraryOut['ContribYield'] = (int) $user->yield_points;
    $libraryOut['TotalPoints'] = (int) $user->points;
    $libraryOut['TotalSoftcorePoints'] = (int) $user->points_softcore;
    $libraryOut['TotalTruePoints'] = (int) $user->points_weighted;
    $libraryOut['Permissions'] = (int) $user->getAttribute('Permissions');
    $libraryOut['Untracked'] = (int) $user->Untracked;
    $libraryOut['ID'] = (int) $user->id;
    $libraryOut['UserWallActive'] = (int) $user->is_user_wall_active;
    $libraryOut['Motto'] = $user->motto;

    $libraryOut['Rank'] = getUserRank($user->username);

    $recentlyPlayedData = [];
    $libraryOut['RecentlyPlayedCount'] = getRecentlyPlayedGames($user, 0, $numGames, $recentlyPlayedData);
    $libraryOut['RecentlyPlayed'] = $recentlyPlayedData;

    if ($libraryOut['RecentlyPlayedCount'] > 0) {
        $gameIDs = [];
        foreach ($recentlyPlayedData as $recentlyPlayed) {
            $gameIDs[] = $recentlyPlayed['GameID'];
        }

        if ($user->last_game_id && !in_array($user->last_game_id, $gameIDs)) {
            $gameIDs[] = $user->last_game_id;
        }

        $userProgress = getUserProgress($user, $gameIDs, $numRecentAchievements, withGameInfo: true);

        $libraryOut['Awarded'] = $userProgress['Awarded'];
        $libraryOut['RecentAchievements'] = $userProgress['RecentAchievements'];
        if (array_key_exists($user->last_game_id, $userProgress['GameInfo'])) {
            $libraryOut['LastGame'] = $userProgress['GameInfo'][$user->last_game_id];
        }
    }

    return $libraryOut;
}

function getUserListByPerms(int $sortBy, int $offset, int $count, ?array &$dataOut, ?string $requestedBy = null, int $perms = Permissions::Unregistered, bool $showUntracked = false): int
{
    $whereQuery = null;
    $permsFilter = null;

    if ($perms >= Permissions::Spam && $perms <= Permissions::Unregistered || $perms == Permissions::JuniorDeveloper) {
        $permsFilter = "ua.Permissions = $perms ";
    } elseif ($perms >= Permissions::Registered && $perms <= Permissions::Moderator) {
        $permsFilter = "ua.Permissions >= $perms ";
    } elseif ($showUntracked) {
        $whereQuery = "WHERE ua.Untracked ";
    } else {
        return 0;
    }

    if ($showUntracked) {
        if ($whereQuery == null) {
            $whereQuery = "WHERE $permsFilter ";
        }
    } else {
        $whereQuery = "WHERE ( NOT ua.Untracked || ua.username = \"$requestedBy\" OR ua.display_name = \"$requestedBy\" ) AND $permsFilter";
    }

    $orderBy = match ($sortBy) {
        1 => "COALESCE(ua.display_name, ua.username) ASC ",
        11 => "COALESCE(ua.display_name, ua.username) DESC ",
        2 => "ua.points DESC ",
        12 => "ua.points ASC ",
        3 => "NumAwarded DESC ",
        13 => "NumAwarded ASC ",
        4 => "ua.last_activity_at DESC ",
        14 => "ua.last_activity_at ASC ",
        default => "COALESCE(ua.display_name, ua.username) ASC ",
    };

   $query = "SELECT ua.id, COALESCE(ua.display_name, ua.username) AS User, ua.points, ua.points_weighted, ua.last_activity_at,
                ua.achievements_unlocked NumAwarded
                FROM users AS ua
                $whereQuery
                ORDER BY $orderBy
                LIMIT $offset, $count";

    $dataOut = legacyDbFetchAll($query)->toArray();

    return count($dataOut);
}

// TODO: Used in developerstats.blade.php. Migrate to a controller.
function getDeveloperStatsTotalCount(int $devFilter = 7): int
{
    $query = User::where('yield_unlocks', '>', 0)
        ->where('yield_points', '>', 0);

    switch ($devFilter) {
        case 1: // Active
            $query->where('Permissions', '>=', Permissions::Developer);
            break;
        case 2: // Junior
            $query->where('Permissions', '=', Permissions::JuniorDeveloper);
            break;
        case 3: // Active + Junior
            $query->where('Permissions', '>=', Permissions::JuniorDeveloper);
            break;
        case 4: // Inactive
            $query->where('Permissions', '<=', Permissions::Registered);
            break;
        case 5: // Active + Inactive
            $query->where('Permissions', '<>', Permissions::JuniorDeveloper);
            break;
        case 6: // Junior + Inactive
            $query->where('Permissions', '<=', Permissions::JuniorDeveloper);
            break;
        default: // Active + Junior + Inactive
            break;
    }

    return $query->count();
}

function GetDeveloperStatsFull(int $count, int $offset = 0, int $sortBy = 0, int $devFilter = 7): array
{
    $stateCond = match ($devFilter) {
        // Active
        1 => " AND ua.Permissions >= " . Permissions::Developer,
        // Junior
        2 => " AND ua.Permissions = " . Permissions::JuniorDeveloper,
        // Active + Junior
        3 => " AND ua.Permissions >= " . Permissions::JuniorDeveloper,
        // Inactive
        4 => " AND ua.Permissions <= " . Permissions::Registered,
        // Active + Inactive
        5 => " AND ua.Permissions <> " . Permissions::JuniorDeveloper,
        // Junior + Inactive
        6 => " AND ua.Permissions <= " . Permissions::JuniorDeveloper,
        // Active + Junior + Inactive
        default => "",
    };
    $stateCond = "ua.yield_unlocks > 0 AND ua.yield_points > 0 " . $stateCond;

    $devs = [];
    $data = [];
    $buildData = function ($query) use (&$devs, &$data) {
        $populateDevs = empty($devs);
        foreach (legacyDbFetchAll($query) as $row) {
            $data[$row['id']] = [
                'Author' => $row['display_name'],
                'Permissions' => $row['Permissions'],
                'yield_unlocks' => $row['yield_unlocks'],
                'yield_points' => $row['yield_points'],
                'last_activity_at' => $row['last_activity_at'],
                'Achievements' => $row['NumAchievements'],
                'OpenTickets' => 0,
                'TicketsResolvedForOthers' => 0,
                'ActiveClaims' => 0,
            ];
            if ($populateDevs) {
                $devs[] = $row['id'];
            }
        }
    };

    $buildDevList = function ($query) use ($count, $offset, $buildData, &$devs) {
        // build an ordered list of the user_ids that will be displayed
        // these will be used to limit the query results of the subsequent queries
        $devs = [];
        foreach (legacyDbFetchAll($query . " LIMIT $offset, $count") as $row) {
            $devs[] = $row['id'];
        }
        if (empty($devs)) {
            return;
        }
        $devList = implode(',', $devs);

        // user data (this must be a LEFT JOIN to pick up users with 0 published achievements)
        $query = "SELECT ua.id, ua.display_name, ua.Permissions, ua.yield_unlocks, ua.yield_points,
                         ua.last_activity_at, SUM(!ISNULL(ach.ID)) AS NumAchievements
                  FROM users ua
                  LEFT JOIN Achievements ach ON ach.user_id = ua.id AND ach.Flags = " . AchievementFlag::OfficialCore->value . "
                  WHERE ua.id IN ($devList)
                  GROUP BY ua.id";
        $buildData($query);
    };

    // determine the top N accounts for each search criteria
    // - use LEFT JOINs and SUM(!ISNULL) to return entries with 0s
    if ($sortBy == 3) { // OpenTickets DESC
        $query = "SELECT ua.id, SUM(!ISNULL(tick.ID)) AS OpenTickets
                  FROM users ua
                  LEFT JOIN Achievements ach ON ach.user_id = ua.id
                  LEFT JOIN Ticket tick ON tick.AchievementID=ach.ID AND tick.ReportState IN (1,3)
                  WHERE $stateCond
                  GROUP BY ua.id
                  ORDER BY OpenTickets DESC, ua.display_name";
        $buildDevList($query);
    } elseif ($sortBy == 4) { // TicketsResolvedForOthers DESC
        $query = "SELECT ua.id, SUM(!ISNULL(ach.ID)) as total
                  FROM users as ua
                  LEFT JOIN Ticket tick ON tick.resolver_id = ua.id AND tick.ReportState = 2 AND tick.resolver_id != tick.reporter_id
                  LEFT JOIN Achievements as ach ON ach.ID = tick.AchievementID AND ach.flags = 3 AND ach.user_id != ua.id
                  WHERE $stateCond
                  GROUP BY ua.id
                  ORDER BY total DESC, ua.display_name";
        $buildDevList($query);
    } elseif ($sortBy == 7) { // ActiveClaims DESC
        $query = "SELECT ua.id, SUM(!ISNULL(sc.ID)) AS ActiveClaims
                  FROM users ua
                  LEFT JOIN SetClaim sc ON sc.user_id=ua.id AND sc.Status IN (" . ClaimStatus::Active . ',' . ClaimStatus::InReview . ")
                  WHERE $stateCond
                  GROUP BY ua.id
                  ORDER BY ActiveClaims DESC, ua.display_name";
        $buildDevList($query);
    } else {
        $order = match ($sortBy) {
            1 => "ua.yield_points DESC, ua.display_name",
            2 => "ua.yield_unlocks DESC, ua.display_name",
            5 => "ua.last_activity_at DESC, ua.display_name",
            6 => "ua.display_name ASC",
            default => "NumAchievements DESC, ua.display_name",
        };

        // ASSERT: yield_points cannot be > 0 unless NumAchievements > 0, so use
        //         INNER JOIN and COUNT for maximum performance.
        // also, build the $dev list directly from these results instead of using
        // one query to build the list and a second query to fetch the user details
        $query = "SELECT ua.id, ua.display_name, ua.Permissions, ua.yield_unlocks, ua.yield_points,
                         ua.last_activity_at, COUNT(*) AS NumAchievements
                  FROM users ua
                  INNER JOIN Achievements ach ON ach.user_id = ua.id AND ach.Flags = 3
                  WHERE $stateCond
                  GROUP BY ua.id
                  ORDER BY $order
                  LIMIT $offset, $count";
        $buildData($query);
    }

    if (empty($devs)) {
        return [];
    }
    $devList = implode(',', $devs);

    // merge in open tickets
    $query = "SELECT ach.user_id as id, COUNT(*) AS OpenTickets
              FROM Ticket tick
              INNER JOIN Achievements ach ON ach.ID=tick.AchievementID
              WHERE ach.user_id IN ($devList)
              AND tick.ReportState IN (1,3)
              GROUP BY ach.user_id";
    foreach (legacyDbFetchAll($query) as $row) {
        $data[$row['id']]['OpenTickets'] = $row['OpenTickets'];
    }

    // merge in tickets resolved for others
    $query = "SELECT tick.resolver_id AS id, COUNT(*) as total
              FROM Ticket AS tick
              INNER JOIN Achievements as ach ON ach.ID = tick.AchievementID
              WHERE tick.resolver_id != tick.reporter_id
              AND ach.user_id != tick.resolver_id
              AND ach.Flags = " . AchievementFlag::OfficialCore->value . "
              AND tick.ReportState = " . TicketState::Resolved . "
              AND tick.resolver_id IN ($devList)
              GROUP BY tick.resolver_id";
    foreach (legacyDbFetchAll($query) as $row) {
        $data[$row['id']]['TicketsResolvedForOthers'] = $row['total'];
    }

    // merge in active claims
    $query = "SELECT ua.id, COUNT(*) AS ActiveClaims
              FROM SetClaim sc
              INNER JOIN users ua ON ua.id=sc.user_id
              WHERE sc.Status IN (" . ClaimStatus::Active . ',' . ClaimStatus::InReview . ")
              AND ua.id IN ($devList)
              GROUP BY ua.id";
    foreach (legacyDbFetchAll($query) as $row) {
        $data[$row['id']]['ActiveClaims'] = $row['ActiveClaims'];
    }

    // generate output sorted by original order
    $results = [];
    foreach ($devs as $dev) {
        $results[] = $data[$dev];
    }

    return $results;
}

function GetUserFields(string $username, array $fields): ?array
{
    sanitize_sql_inputs($username);

    $fieldsCSV = implode(",", $fields);
    $query = "SELECT $fieldsCSV FROM users AS ua
              WHERE ua.username = '$username'";
    $dbResult = s_mysql_query($query);

    if (!$dbResult) {
        return null;
    }

    return mysqli_fetch_assoc($dbResult);
}

/**
 * Gets completed and mastered counts for all users who have played the passed in games.
 */
function getMostAwardedUsers(array $gameIDs): array
{
    $retVal = [];
    if (empty($gameIDs)) {
        return $retVal;
    }

    $query = "SELECT ua.username AS User,
              SUM(IF(AwardType LIKE " . AwardType::GameBeaten . " AND AwardDataExtra LIKE '0', 1, 0)) AS BeatenSoftcore,
              SUM(IF(AwardType LIKE " . AwardType::GameBeaten . " AND AwardDataExtra LIKE '1', 1, 0)) AS BeatenHardcore,
              SUM(IF(AwardType LIKE " . AwardType::Mastery . " AND AwardDataExtra LIKE '0', 1, 0)) AS Completed,
              SUM(IF(AwardType LIKE " . AwardType::Mastery . " AND AwardDataExtra LIKE '1', 1, 0)) AS Mastered
              FROM SiteAwards AS sa
              LEFT JOIN users AS ua ON ua.id = sa.user_id
              WHERE sa.AwardType IN (" . implode(',', AwardType::game()) . ")
              AND AwardData IN (" . implode(",", $gameIDs) . ")
              AND Untracked = 0
              GROUP BY ua.username
              ORDER BY ua.username";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }

    return $retVal;
}

/**
 * Gets completed and mastered counts for all the passed in games.
 */
function getMostAwardedGames(array $gameIDs): array
{
    $retVal = [];
    if (empty($gameIDs)) {
        return $retVal;
    }

    $query = "SELECT gd.Title, sa.AwardData AS ID, c.Name AS ConsoleName, gd.ImageIcon as GameIcon,
              SUM(IF(AwardType LIKE " . AwardType::GameBeaten . " AND AwardDataExtra LIKE '0' AND Untracked = 0, 1, 0)) AS BeatenSoftcore,
              SUM(IF(AwardType LIKE " . AwardType::GameBeaten . " AND AwardDataExtra LIKE '1' AND Untracked = 0, 1, 0)) AS BeatenHardcore,
              SUM(IF(AwardType LIKE " . AwardType::Mastery . " AND AwardDataExtra LIKE '0' AND Untracked = 0, 1, 0)) AS Completed,
              SUM(IF(AwardType LIKE " . AwardType::Mastery . " AND AwardDataExtra LIKE '1' AND Untracked = 0, 1, 0)) AS Mastered
              FROM SiteAwards AS sa
              LEFT JOIN GameData AS gd ON gd.ID = sa.AwardData
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              LEFT JOIN users AS ua ON ua.id = sa.user_id
              WHERE sa.AwardType IN (" . implode(',', AwardType::game()) . ")
              AND AwardData IN(" . implode(",", $gameIDs) . ")
              GROUP BY sa.AwardData, gd.Title
              ORDER BY Title";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }

    return $retVal;
}
