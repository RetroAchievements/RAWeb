<?php

use App\Community\Enums\ClaimStatus;
use App\Community\Enums\TicketState;
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

    sanitize_sql_inputs($user);

    $query = "SELECT ID FROM UserAccounts WHERE User LIKE '$user'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);

        return (int) ($data['ID'] ?? 0);
    }

    // cannot find user $user
    return 0;
}

function getUserMetadataFromID(int $userID): ?array
{
    sanitize_sql_inputs($userID);

    $query = "SELECT * FROM UserAccounts WHERE ID ='$userID'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult);
    }

    return null;
}

function getUserUnlockDates(string $user, int $gameID, ?array &$dataOut): int
{
    sanitize_sql_inputs($user, $gameID);

    $query = "SELECT ach.ID, ach.Title, ach.Description, ach.Points, ach.BadgeName, aw.HardcoreMode, aw.Date
        FROM Achievements ach
        INNER JOIN Awarded AS aw ON ach.ID = aw.AchievementID
        WHERE ach.GameID = $gameID AND aw.User = '$user'
        ORDER BY ach.ID, aw.HardcoreMode DESC";

    $dbResult = s_mysql_query($query);

    $dataOut = [];

    if (!$dbResult) {
        return 0;
    }

    $lastID = 0;
    while ($data = mysqli_fetch_assoc($dbResult)) {
        $achID = $data['ID'];
        if ($lastID == $achID) {
            continue;
        }

        $dataOut[] = $data;
        $lastID = $achID;
    }

    return count($dataOut);
}

/**
 * @param array<string, mixed>|null $dataOut
 */
function getUserUnlocksDetailed(string $user, int $gameID, ?array &$dataOut): int
{
    sanitize_sql_inputs($user, $gameID);

    $query = "SELECT ach.Title, ach.ID, ach.Points, aw.HardcoreMode
        FROM Achievements AS ach
        LEFT JOIN Awarded AS aw ON ach.ID = aw.AchievementID
        WHERE ach.GameID = '$gameID' AND aw.User = '$user'
        ORDER BY ach.ID, aw.HardcoreMode ";

    $dbResult = s_mysql_query($query);

    $dataOut = [];

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $dataOut[] = $data;
        }
    }

    return count($dataOut);
}

function validateUsername(string $userIn): ?string
{
    $user = User::firstWhere('User', $userIn);

    return ($user !== null) ? $user->User : null;
}

// TODO replace with created and lastLogin timestamps on user
function getUserActivityRange(string $user, ?string &$firstLogin, ?string &$lastLogin): bool
{
    sanitize_sql_inputs($user);

    $query = "SELECT MIN(act.timestamp) AS FirstLogin, MAX(act.timestamp) AS LastLogin
              FROM Activity AS act
              WHERE act.User = '$user' AND act.activitytype=2";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        $firstLogin = $data['FirstLogin'];
        $lastLogin = $data['LastLogin'];

        return !empty($firstLogin) || !empty($lastLogin);
    }

    return false;
}

function getUserPageInfo(string $user, int $numGames = 0, int $numRecentAchievements = 0): array
{
    if (!getAccountDetails($user, $userInfo)) {
        return [];
    }

    $libraryOut = [];

    $libraryOut['User'] = $userInfo['User'];
    $libraryOut['MemberSince'] = $userInfo['Created'];
    $libraryOut['LastActivity'] = $userInfo['LastLogin'];
    $libraryOut['LastActivityID'] = $userInfo['LastActivityID'];
    $libraryOut['RichPresenceMsg'] = empty($userInfo['RichPresenceMsg']) || $userInfo['RichPresenceMsg'] === 'Unknown' ? null : $userInfo['RichPresenceMsg'];
    $libraryOut['LastGameID'] = (int) $userInfo['LastGameID'];
    $libraryOut['ContribCount'] = (int) $userInfo['ContribCount'];
    $libraryOut['ContribYield'] = (int) $userInfo['ContribYield'];
    $libraryOut['TotalPoints'] = (int) $userInfo['RAPoints'];
    $libraryOut['TotalSoftcorePoints'] = (int) $userInfo['RASoftcorePoints'];
    $libraryOut['TotalTruePoints'] = (int) $userInfo['TrueRAPoints'];
    $libraryOut['Permissions'] = (int) $userInfo['Permissions'];
    $libraryOut['Untracked'] = (int) $userInfo['Untracked'];
    $libraryOut['ID'] = (int) $userInfo['ID'];
    $libraryOut['UserWallActive'] = (int) $userInfo['UserWallActive'];
    $libraryOut['Motto'] = $userInfo['Motto'];

    $libraryOut['Rank'] = getUserRank($user);

    $libraryOut['RecentlyPlayedCount'] = getRecentlyPlayedGames($user, 0, $numGames, $recentlyPlayedData);
    $libraryOut['RecentlyPlayed'] = $recentlyPlayedData;

    if ($libraryOut['RecentlyPlayedCount'] > 0) {
        $gameIDs = [];
        foreach ($recentlyPlayedData as $recentlyPlayed) {
            $gameIDs[] = $recentlyPlayed['GameID'];
        }

        if (!in_array($userInfo['LastGameID'], $gameIDs)) {
            $gameIDs[] = $userInfo['LastGameID'];
        }

        $userProgress = getUserProgress($user, $gameIDs, $numRecentAchievements, withGameInfo: true);

        $libraryOut['Awarded'] = $userProgress['Awarded'];
        $libraryOut['RecentAchievements'] = $userProgress['RecentAchievements'];
        if (array_key_exists($userInfo['LastGameID'], $userProgress['GameInfo'])) {
            $libraryOut['LastGame'] = $userProgress['GameInfo'][$userInfo['LastGameID']];
        }
    }

    return $libraryOut;
}

function getControlPanelUserInfo(string $user, ?array &$libraryOut): bool
{
    sanitize_sql_inputs($user);

    $libraryOut = [];
    $libraryOut['Played'] = [];
    // getUserActivityRange( $user, $firstLogin, $lastLogin );
    // $libraryOut['MemberSince'] = $firstLogin;
    // $libraryOut['LastLogin'] = $lastLogin;

    $query = "SELECT gd.ID, c.Name AS ConsoleName, gd.Title AS GameTitle, COUNT(*) AS NumAwarded, Inner1.NumPossible
                FROM Awarded AS aw
                LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
                LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
                LEFT JOIN (
                    SELECT ach.GameID, COUNT(*) AS NumPossible
                    FROM Achievements AS ach
                    WHERE ach.Flags = 3
                    GROUP BY ach.GameID ) AS Inner1 ON Inner1.GameID = gd.ID
                WHERE aw.User = '$user' AND aw.HardcoreMode = 0
                GROUP BY gd.ID, gd.ConsoleID, gd.Title
                ORDER BY gd.Title, gd.ConsoleID";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();

        return false;
    }

    while ($db_entry = mysqli_fetch_assoc($dbResult)) {
        $libraryOut['Played'][] = $db_entry;
    }   // use as raw array to preserve order!

    return true;
}

function getUserListByPerms(int $sortBy, int $offset, int $count, ?array &$dataOut, ?string $requestedBy = null, int $perms = Permissions::Unregistered, bool $showUntracked = false): int
{
    $whereQuery = null;
    $permsFilter = null;

    if ($perms >= Permissions::Spam && $perms <= Permissions::Unregistered || $perms == Permissions::JuniorDeveloper) {
        $permsFilter = "ua.Permissions = $perms ";
    } elseif ($perms >= Permissions::Registered && $perms <= Permissions::Admin) {
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

    // TODO slow query (70) when ordering by NumAwarded
    $query = "SELECT ua.ID, ua.User, ua.RAPoints, ua.TrueRAPoints, ua.LastLogin,
                (SELECT COUNT(*) AS NumAwarded FROM Awarded AS aw WHERE aw.User = ua.User) NumAwarded
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

    $order = match ($sortBy) {
        // number of points allocated
        1 => "ContribYield DESC",
        // number of achievements won by others
        2 => "ContribCount DESC",
        3 => "OpenTickets DESC",
        4 => "TicketsResolvedForOthers DESC",
        5 => "LastLogin DESC",
        6 => "Author ASC",
        7 => "ActiveClaims DESC",
        default => "Achievements DESC",
    };

    $query = "
    SELECT
        ua.User AS Author,
        Permissions,
        ContribCount,
        ContribYield,
        COUNT(DISTINCT(IF(ach.Flags = 3, ach.ID, NULL))) AS Achievements,
        COUNT(DISTINCT(tick.ID)) AS OpenTickets,
        COALESCE(resolved.total,0) AS TicketsResolvedForOthers,
        LastLogin,
        COUNT(DISTINCT(sc.ID)) AS ActiveClaims
    FROM
        UserAccounts AS ua
    LEFT JOIN
        Achievements AS ach ON (ach.Author = ua.User AND ach.Flags IN (3, 5))
    LEFT JOIN
        Ticket AS tick ON (tick.AchievementID = ach.ID AND tick.ReportState IN (" . TicketState::Open . "," . TicketState::Request . "))
    LEFT JOIN
        SetClaim AS sc ON (sc.User = ua.User AND sc.Status = " . ClaimStatus::Active . ")
    LEFT JOIN (
        SELECT ua2.User,
        SUM(CASE WHEN t.ReportState = 2 THEN 1 ELSE 0 END) AS total
        FROM Ticket AS t
        LEFT JOIN UserAccounts as ua ON ua.ID = t.ReportedByUserID
        LEFT JOIN UserAccounts as ua2 ON ua2.ID = t.ResolvedByUserID
        LEFT JOIN Achievements as a ON a.ID = t.AchievementID
        WHERE ua.User NOT LIKE ua2.User
        AND a.Author NOT LIKE ua2.User
        AND a.Flags = '3' 
        GROUP BY ua2.User) resolved ON resolved.User = ua.User
    WHERE
        ContribCount > 0 AND ContribYield > 0
        $stateCond
    GROUP BY
        ua.User
    ORDER BY
        $order,
        OpenTickets ASC";
    // LIMIT 0, $count";

    return legacyDbFetchAll($query)->toArray();
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
    $query = "SELECT ua.User,
              SUM(IF(AwardDataExtra LIKE '0', 1, 0)) AS Completed,
              SUM(IF(AwardDataExtra LIKE '1', 1, 0)) AS Mastered
              FROM SiteAwards AS sa
              LEFT JOIN UserAccounts AS ua ON ua.User = sa.User
              WHERE AwardType LIKE '1'
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
    $query = "SELECT gd.Title, sa.AwardData AS ID, c.Name AS ConsoleName, gd.ImageIcon as GameIcon,
              SUM(IF(AwardDataExtra LIKE '0' AND Untracked = 0, 1, 0)) AS Completed,
              SUM(IF(AwardDataExtra LIKE '1' AND Untracked = 0, 1, 0)) AS Mastered
              FROM SiteAwards AS sa
              LEFT JOIN GameData AS gd ON gd.ID = sa.AwardData
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              LEFT JOIN UserAccounts AS ua ON ua.User = sa.User
              WHERE sa.AwardType LIKE '1'
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
