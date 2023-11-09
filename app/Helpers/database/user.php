<?php

use App\Community\Enums\AwardType;
use App\Community\Enums\ClaimStatus;
use App\Site\Enums\Permissions;
use App\Site\Models\User;

function GetUserData(string $username): ?array
{
    return User::firstWhere('User', $username)?->toArray();
}

function getAccountDetails(?string &$username = null, ?array &$dataOut = []): bool
{
    if (empty($username) || !isValidUsername($username)) {
        return false;
    }

    $query = "SELECT ID, User, EmailAddress, Permissions, RAPoints, RASoftcorePoints, TrueRAPoints,
                     cookie, websitePrefs, UnreadMessageCount, Motto, UserWallActive,
                     APIKey, ContribCount, ContribYield,
                     RichPresenceMsg, LastGameID, LastLogin, LastActivityID,
                     Created, DeleteRequested, Untracked
                FROM UserAccounts
                WHERE User = :username
                AND Deleted IS NULL";

    $dataOut = legacyDbFetch($query, [
        'username' => $username,
    ]);

    if (!$dataOut) {
        return false;
    }

    $username = $dataOut['User'];

    return true;
}

function getUserIDFromUser(?string $user): int
{
    if (!$user) {
        return 0;
    }

    $query = "SELECT ID FROM UserAccounts WHERE User = :user";
    $row = legacyDbFetch($query, ['user' => $user]);

    return $row ? (int) $row['ID'] : 0;
}

function getUserMetadataFromID(int $userID): ?array
{
    $query = "SELECT * FROM UserAccounts WHERE ID ='$userID'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult);
    }

    return null;
}

function validateUsername(string $userIn): ?string
{
    $user = User::firstWhere('User', $userIn);

    return ($user !== null) ? $user->User : null;
}

function getUserPageInfo(string $username, int $numGames = 0, int $numRecentAchievements = 0, bool $isAuthenticated = false): array
{
    $user = User::firstWhere('User', $username);
    if (!$user) {
        return [];
    }

    $libraryOut = [];

    $libraryOut['User'] = $user->User;
    $libraryOut['MemberSince'] = $user->Created?->__toString();
    $libraryOut['LastActivity'] = $user->LastLogin?->__toString();
    $libraryOut['LastActivityID'] = $user->LastActivityID;
    $libraryOut['RichPresenceMsg'] = empty($user->RichPresenceMsg) || $user->RichPresenceMsg === 'Unknown' ? null : $user->RichPresenceMsg;
    $libraryOut['LastGameID'] = (int) $user->LastGameID;
    $libraryOut['ContribCount'] = (int) $user->ContribCount;
    $libraryOut['ContribYield'] = (int) $user->ContribYield;
    $libraryOut['TotalPoints'] = (int) $user->RAPoints;
    $libraryOut['TotalSoftcorePoints'] = (int) $user->RASoftcorePoints;
    $libraryOut['TotalTruePoints'] = (int) $user->TrueRAPoints;
    $libraryOut['Permissions'] = (int) $user->getAttribute('Permissions');
    $libraryOut['Untracked'] = (int) $user->Untracked;
    $libraryOut['ID'] = (int) $user->ID;
    $libraryOut['UserWallActive'] = (int) $user->UserWallActive;
    $libraryOut['Motto'] = $user->Motto;

    $libraryOut['Rank'] = getUserRank($user->User);

    $recentlyPlayedData = [];
    $libraryOut['RecentlyPlayedCount'] = $isAuthenticated ? getRecentlyPlayedGames($user->User, 0, $numGames, $recentlyPlayedData) : 0;
    $libraryOut['RecentlyPlayed'] = $recentlyPlayedData;

    if ($libraryOut['RecentlyPlayedCount'] > 0) {
        $gameIDs = [];
        foreach ($recentlyPlayedData as $recentlyPlayed) {
            $gameIDs[] = $recentlyPlayed['GameID'];
        }

        if ($user->LastGameID && !in_array($user->LastGameID, $gameIDs)) {
            $gameIDs[] = $user->LastGameID;
        }

        $userProgress = getUserProgress($user, $gameIDs, $numRecentAchievements, withGameInfo: true);

        $libraryOut['Awarded'] = $userProgress['Awarded'];
        $libraryOut['RecentAchievements'] = $userProgress['RecentAchievements'];
        if (array_key_exists($user->LastGameID, $userProgress['GameInfo'])) {
            $libraryOut['LastGame'] = $userProgress['GameInfo'][$user->LastGameID];
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
        $whereQuery = "WHERE ( NOT ua.Untracked || ua.User = \"$requestedBy\" ) AND $permsFilter";
    }

    $orderBy = match ($sortBy) {
        1 => "ua.User ASC ",
        11 => "ua.User DESC ",
        2 => "ua.RAPoints DESC ",
        12 => "ua.RAPoints ASC ",
        3 => "NumAwarded DESC ",
        13 => "NumAwarded ASC ",
        4 => "ua.LastLogin DESC ",
        14 => "ua.LastLogin ASC ",
        default => "ua.User ASC ",
    };

    $query = "SELECT ua.ID, ua.User, ua.RAPoints, ua.TrueRAPoints, ua.LastLogin,
                ua.achievements_unlocked NumAwarded
                FROM UserAccounts AS ua
                $whereQuery
                ORDER BY $orderBy
                LIMIT $offset, $count";

    $dataOut = legacyDbFetchAll($query)->toArray();

    return count($dataOut);
}

function GetDeveloperStatsFull(int $count, int $sortBy, int $devFilter = 7): array
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
    $stateCond = "ua.ContribCount > 0 AND ua.ContribYield > 0 " . $stateCond;

    $devs = [];

    $data = [];
    $buildData = function ($query) use (&$devs, &$data) {
        $populateDevs = empty($devs);
        foreach (legacyDbFetchAll($query) as $row) {
            $data[$row['ID']] = [
                'Author' => $row['User'],
                'Permissions' => $row['Permissions'],
                'ContribCount' => $row['ContribCount'],
                'ContribYield' => $row['ContribYield'],
                'LastLogin' => $row['LastLogin'],
                'Achievements' => $row['NumAchievements'],
                'OpenTickets' => 0,
                'TicketsResolvedForOthers' => 0,
                'ActiveClaims' => 0,
            ];
            if ($populateDevs) {
                $devs[] = $row['ID'];
            }
        }
    };

    $buildDevList = function ($query) use ($count, $buildData, &$devs) {
        // build an ordered list of the user_ids that will be displayed
        // these will be used to limit the query results of the subsequent queries
        $devs = [];
        foreach (legacyDbFetchAll($query . " LIMIT $count") as $row) {
            $devs[] = $row['ID'];
        }
        if (empty($devs)) {
            return [];
        }
        $devList = implode(',', $devs);

        // user data (this must be a LEFT JOIN to pick up users with 0 published achievements)
        $query = "SELECT ua.ID, ua.User, ua.Permissions, ua.ContribCount, ua.ContribYield,
                         ua.LastLogin, COUNT(*) AS NumAchievements
                  FROM UserAccounts ua
                  LEFT JOIN Achievements ach ON ach.Author=ua.User AND ach.Flags = 3
                  WHERE ua.ID IN ($devList)
                  GROUP BY ua.ID";
        $buildData($query);
    };

    // determine the top N accounts for each search criteria
    // - use LEFT JOINs and SUM(!ISNULL) to return entries with 0s
    if ($sortBy == 3) { // OpenTickets DESC
        $query = "SELECT ua.ID, SUM(!ISNULL(tick.ID)) AS OpenTickets
                  FROM UserAccounts ua
                  LEFT JOIN Achievements ach ON ach.Author=ua.User
                  LEFT JOIN Ticket tick ON tick.AchievementID=ach.ID AND tick.ReportState IN (1,3)
                  WHERE $stateCond
                  GROUP BY ua.ID
                  ORDER BY OpenTickets DESC";
        $buildDevList($query);
    } elseif ($sortBy == 4) { // TicketsResolvedForOthers DESC
        $query = "SELECT ua.ID, SUM(!ISNULL(tick.ID)) as total
                  FROM UserAccounts as ua
                  LEFT JOIN Ticket tick ON tick.ResolvedByUserID = ua.ID AND tick.ReportState = 2 AND tick.ResolvedByUserID != tick.ReportedByUserID
                  LEFT JOIN Achievements as ach ON ach.ID = tick.AchievementID AND ach.flags = 3 AND ach.Author != ua.User
                  WHERE $stateCond
                  GROUP BY ua.ID
                  ORDER BY total DESC";
        $buildDevList($query);
    } elseif ($sortBy == 7) { // ActiveClaims DESC
        $query = "SELECT ua.ID, SUM(!ISNULL(sc.ID)) AS ActiveClaims
                  FROM UserAccounts ua
                  LEFT JOIN SetClaim sc ON sc.User=ua.User AND sc.Status IN (" . ClaimStatus::Active . ',' . ClaimStatus::InReview . ")
                  WHERE $stateCond
                  GROUP BY ua.ID
                  ORDER BY ActiveClaims DESC";
        $buildDevList($query);
    } else {
        $order = match ($sortBy) {
            1 => "ua.ContribYield DESC",
            2 => "ua.ContribCount DESC",
            5 => "ua.LastLogin DESC",
            6 => "ua.User ASC",
            default => "NumAchievements DESC",
        };

        // ASSERT: ContribYield cannot be > 0 unless NumAchievements > 0, so use
        //         INNER JOIN and COUNT for maximum performance.
        // also, build the $dev list directly from these results instead of using
        // one query to build the list and a second query to fetch the user details
        $query = "SELECT ua.ID, ua.User, ua.Permissions, ua.ContribCount, ua.ContribYield,
                         ua.LastLogin, COUNT(*) AS NumAchievements
                  FROM UserAccounts ua
                  INNER JOIN Achievements ach ON ach.Author=ua.User AND ach.Flags = 3
                  WHERE $stateCond
                  GROUP BY ua.ID
                  ORDER BY $order
                  LIMIT $count";
        $buildData($query);
    }

    if (empty($devs)) {
        return [];
    }
    $devList = implode(',', $devs);

    // merge in open tickets
    $query = "SELECT ua.ID, COUNT(*) AS OpenTickets
              FROM Ticket tick
              INNER JOIN Achievements ach ON ach.ID=tick.AchievementID
              INNER JOIN UserAccounts ua ON ua.User=ach.Author
              WHERE ua.ID IN ($devList)
              AND tick.ReportState IN (1,3)
              GROUP BY ua.ID";
    foreach (legacyDbFetchAll($query) as $row) {
        $data[$row['ID']]['OpenTickets'] = $row['OpenTickets'];
    }

    // merge in tickets resolved for others
    $query = "SELECT ua.ID, COUNT(*) as total
              FROM Ticket AS tick
              INNER JOIN UserAccounts as ua ON ua.ID = tick.ResolvedByUserID
              INNER JOIN Achievements as ach ON ach.ID = tick.AchievementID
              WHERE tick.ResolvedByUserID != tick.ReportedByUserID
              AND ach.Author != ua.User
              AND ach.Flags = 3
              AND tick.ReportState = 2
              AND ua.ID IN ($devList)
              GROUP BY ua.ID";
    foreach (legacyDbFetchAll($query) as $row) {
        $data[$row['ID']]['TicketsResolvedForOthers'] = $row['total'];
    }

    // merge in active claims
    $query = "SELECT ua.ID, COUNT(*) AS ActiveClaims
              FROM SetClaim sc
              INNER JOIN UserAccounts ua ON ua.User=sc.User
              WHERE sc.Status IN (" . ClaimStatus::Active . ',' . ClaimStatus::InReview . ")
              AND ua.ID IN ($devList)
              GROUP BY ua.ID";
    foreach (legacyDbFetchAll($query) as $row) {
        $data[$row['ID']]['ActiveClaims'] = $row['ActiveClaims'];
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
    $query = "SELECT $fieldsCSV FROM UserAccounts AS ua
              WHERE ua.User = '$username'";
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

    $query = "SELECT ua.User,
              SUM(IF(AwardType LIKE " . AwardType::GameBeaten . " AND AwardDataExtra LIKE '0', 1, 0)) AS BeatenSoftcore,
              SUM(IF(AwardType LIKE " . AwardType::GameBeaten . " AND AwardDataExtra LIKE '1', 1, 0)) AS BeatenHardcore,
              SUM(IF(AwardType LIKE " . AwardType::Mastery . " AND AwardDataExtra LIKE '0', 1, 0)) AS Completed,
              SUM(IF(AwardType LIKE " . AwardType::Mastery . " AND AwardDataExtra LIKE '1', 1, 0)) AS Mastered
              FROM SiteAwards AS sa
              LEFT JOIN UserAccounts AS ua ON ua.User = sa.User
              WHERE sa.AwardType IN (" . implode(',', AwardType::game()) . ")
              AND AwardData IN (" . implode(",", $gameIDs) . ")
              AND Untracked = 0
              GROUP BY User
              ORDER BY User";

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
              LEFT JOIN UserAccounts AS ua ON ua.User = sa.User
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
