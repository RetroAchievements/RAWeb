<?php

use App\Community\Enums\AwardType;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\TicketState;
use App\Enums\Permissions;
use App\Models\User;

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
    $libraryOut['LastGameID'] = (int) $user->rich_presence_game_id;
    $libraryOut['ContribCount'] = (int) $user->yield_unlocks;
    $libraryOut['ContribYield'] = (int) $user->yield_points;
    $libraryOut['TotalPoints'] = (int) $user->points_hardcore;
    $libraryOut['TotalSoftcorePoints'] = (int) $user->points;
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

        if ($user->rich_presence_game_id && !in_array($user->rich_presence_game_id, $gameIDs)) {
            $gameIDs[] = $user->rich_presence_game_id;
        }

        $userProgress = getUserProgress($user, $gameIDs, $numRecentAchievements, withGameInfo: true);

        $libraryOut['Awarded'] = $userProgress['Awarded'];
        $libraryOut['RecentAchievements'] = $userProgress['RecentAchievements'];
        if (array_key_exists($user->rich_presence_game_id, $userProgress['GameInfo'])) {
            $libraryOut['LastGame'] = $userProgress['GameInfo'][$user->rich_presence_game_id];
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
        2 => "ua.points_hardcore DESC ",
        12 => "ua.points_hardcore ASC ",
        3 => "NumAwarded DESC ",
        13 => "NumAwarded ASC ",
        4 => "ua.last_activity_at DESC ",
        14 => "ua.last_activity_at ASC ",
        default => "COALESCE(ua.display_name, ua.username) ASC ",
    };

   $query = "SELECT ua.id, COALESCE(ua.display_name, ua.username) AS User, ua.points_hardcore AS points, ua.points_weighted, ua.last_activity_at,
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
                         ua.last_activity_at, SUM(!ISNULL(ach.id)) AS NumAchievements
                  FROM users ua
                  LEFT JOIN achievements ach ON ach.user_id = ua.id AND ach.is_promoted = 1
                  WHERE ua.id IN ($devList)
                  GROUP BY ua.id";
        $buildData($query);
    };

    // determine the top N accounts for each search criteria
    // - use LEFT JOINs and SUM(!ISNULL) to return entries with 0s
    if ($sortBy == 3) { // OpenTickets DESC
        $query = "SELECT ua.id, SUM(!ISNULL(tick.id)) AS OpenTickets
                  FROM users ua
                  LEFT JOIN achievements ach ON ach.user_id = ua.id
                  LEFT JOIN tickets tick ON tick.ticketable_id=ach.id AND tick.ticketable_type='achievement' AND tick.state IN ('open','request')
                  WHERE $stateCond
                  GROUP BY ua.id
                  ORDER BY OpenTickets DESC, ua.display_name";
        $buildDevList($query);
    } elseif ($sortBy == 4) { // TicketsResolvedForOthers DESC
        $query = "SELECT ua.id, SUM(!ISNULL(ach.id)) as total
                  FROM users as ua
                  LEFT JOIN tickets tick ON tick.resolver_id = ua.id AND tick.state = 'resolved' AND tick.resolver_id != tick.reporter_id
                  LEFT JOIN achievements as ach ON ach.id = tick.ticketable_id AND ach.is_promoted = 1 AND ach.user_id != ua.id
                  WHERE $stateCond
                  GROUP BY ua.id
                  ORDER BY total DESC, ua.display_name";
        $buildDevList($query);
    } elseif ($sortBy == 7) { // ActiveClaims DESC
        $query = "SELECT ua.id, SUM(!ISNULL(sc.id)) AS ActiveClaims
                  FROM users ua
                  LEFT JOIN achievement_set_claims sc ON sc.user_id=ua.id AND sc.status IN ('" . ClaimStatus::Active->value . "','" . ClaimStatus::InReview->value . "')
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
                  INNER JOIN achievements ach ON ach.user_id = ua.id AND ach.is_promoted = 1
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
              FROM tickets tick
              INNER JOIN achievements ach ON ach.id=tick.ticketable_id
              WHERE tick.ticketable_type = 'achievement'
              AND ach.user_id IN ($devList)
              AND tick.state IN ('open','request')
              GROUP BY ach.user_id";
    foreach (legacyDbFetchAll($query) as $row) {
        $data[$row['id']]['OpenTickets'] = $row['OpenTickets'];
    }

    // merge in tickets resolved for others
    $query = "SELECT tick.resolver_id AS id, COUNT(*) as total
              FROM tickets AS tick
              INNER JOIN achievements as ach ON ach.id = tick.ticketable_id
              WHERE tick.ticketable_type = 'achievement'
              AND tick.resolver_id != tick.reporter_id
              AND ach.user_id != tick.resolver_id
              AND ach.is_promoted = 1
              AND tick.state = '" . TicketState::Resolved->value . "'
              AND tick.resolver_id IN ($devList)
              GROUP BY tick.resolver_id";
    foreach (legacyDbFetchAll($query) as $row) {
        $data[$row['id']]['TicketsResolvedForOthers'] = $row['total'];
    }

    // merge in active claims
    $query = "SELECT ua.id, COUNT(*) AS ActiveClaims
              FROM achievement_set_claims sc
              INNER JOIN users ua ON ua.id=sc.user_id
              WHERE sc.status IN ('" . ClaimStatus::Active->value . "','" . ClaimStatus::InReview->value . "')
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

    $gameAwardValues = implode("','", AwardType::gameValues());

    $query = "SELECT ua.username AS User,
              SUM(IF(award_type = '" . AwardType::GameBeaten->value . "' AND award_tier = 0, 1, 0)) AS BeatenSoftcore,
              SUM(IF(award_type = '" . AwardType::GameBeaten->value . "' AND award_tier = 1, 1, 0)) AS BeatenHardcore,
              SUM(IF(award_type = '" . AwardType::Mastery->value . "' AND award_tier = 0, 1, 0)) AS Completed,
              SUM(IF(award_type = '" . AwardType::Mastery->value . "' AND award_tier = 1, 1, 0)) AS Mastered
              FROM user_awards AS sa
              LEFT JOIN users AS ua ON ua.id = sa.user_id
              WHERE sa.award_type IN ('{$gameAwardValues}')
              AND award_key IN (" . implode(",", $gameIDs) . ")
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

    $gameAwardValues = implode("','", AwardType::gameValues());

    $query = "SELECT gd.title AS Title, sa.award_key AS ID, s.name AS ConsoleName, gd.image_icon_asset_path as GameIcon,
              SUM(IF(award_type = '" . AwardType::GameBeaten->value . "' AND award_tier = 0 AND Untracked = 0, 1, 0)) AS BeatenSoftcore,
              SUM(IF(award_type = '" . AwardType::GameBeaten->value . "' AND award_tier = 1 AND Untracked = 0, 1, 0)) AS BeatenHardcore,
              SUM(IF(award_type = '" . AwardType::Mastery->value . "' AND award_tier = 0 AND Untracked = 0, 1, 0)) AS Completed,
              SUM(IF(award_type = '" . AwardType::Mastery->value . "' AND award_tier = 1 AND Untracked = 0, 1, 0)) AS Mastered
              FROM user_awards AS sa
              LEFT JOIN games AS gd ON gd.id = sa.award_key
              LEFT JOIN systems AS s ON s.id = gd.system_id
              LEFT JOIN users AS ua ON ua.id = sa.user_id
              WHERE sa.award_type IN ('{$gameAwardValues}')
              AND award_key IN(" . implode(",", $gameIDs) . ")
              GROUP BY sa.award_key, gd.title
              ORDER BY Title";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }

    return $retVal;
}
