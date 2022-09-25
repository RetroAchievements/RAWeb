<?php

use RA\AwardThreshold;
use RA\ClaimStatus;
use RA\Permissions;
use RA\TicketState;

function GetUserData($user): ?array
{
    sanitize_sql_inputs($user);

    $query = "SELECT * FROM UserAccounts WHERE User='$user'";
    $dbResult = s_mysql_query($query);

    if (!$dbResult || mysqli_num_rows($dbResult) != 1) {
        log_sql_fail();

        // failed: Achievement $id doesn't exist!
        return null;
    }

    return mysqli_fetch_assoc($dbResult);
}

function getAccountDetails(&$user, &$dataOut): bool
{
    if (!isset($user) || mb_strlen($user) < 2) {
        return false;
    }

    sanitize_sql_inputs($user);

    $query = "SELECT ID, User, EmailAddress, Permissions, RAPoints, RASoftcorePoints, TrueRAPoints,
                     cookie, websitePrefs, UnreadMessageCount, Motto, UserWallActive,
                     APIKey, ContribCount, ContribYield,
                     RichPresenceMsg, LastGameID, LastLogin, LastActivityID,
                     Created, DeleteRequested, Untracked
                FROM UserAccounts
                WHERE User='$user'
                AND Deleted IS NULL";

    $dbResult = s_mysql_query($query);
    if (!$dbResult || mysqli_num_rows($dbResult) !== 1) {
        return false;
    }

    $dataOut = mysqli_fetch_array($dbResult);
    $user = $dataOut['User'];    // Fix case!

    return true;
}

function getUserIDFromUser($user): int
{
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

function getUserFromID($userID): string
{
    sanitize_sql_inputs($userID);

    $query = "SELECT User FROM UserAccounts WHERE ID ='$userID'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);

        return $data ? (string) $data['User'] : '';
    }

    return '';
}

function getUserMetadataFromID($userID): ?array
{
    sanitize_sql_inputs($userID);

    $query = "SELECT * FROM UserAccounts WHERE ID ='$userID'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult);
    }

    return null;
}

function getUserUnlockDates($user, $gameID, &$dataOut): int
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

function getUserUnlocksDetailed($user, $gameID, &$dataOut): int
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

function GetUserUnlocksData($user, $gameID, $hardcoreMode): array
{
    sanitize_sql_inputs($user, $gameID);

    $query = "SELECT AchievementID
        FROM Achievements AS ach
        LEFT JOIN Awarded AS aw ON ach.ID = aw.AchievementID
        WHERE ach.GameID = '$gameID' AND aw.User = '$user' AND aw.HardcoreMode = $hardcoreMode ";

    $dbResult = s_mysql_query($query);

    $retVal = [];
    while ($db_entry = mysqli_fetch_assoc($dbResult)) {
        settype($db_entry['AchievementID'], 'integer');
        $retVal[] = $db_entry['AchievementID'];
    }

    return $retVal;
}

function validateUsername($userIn): ?string
{
    sanitize_sql_inputs($userIn);

    $query = "SELECT uc.User FROM UserAccounts AS uc WHERE uc.User LIKE '$userIn'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);

        return (string) $data['User'];
    } else {
        log_sql_fail();

        return null;
    }
}

/**
 * Gets the account age in years for the input user.
 */
function getAccountAge(string $user): int
{
    sanitize_sql_inputs($user);

    $query = "SELECT ua.Created
              FROM UserAccounts AS ua
              WHERE ua.User='$user'";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return 0;
    }

    $result = mysqli_fetch_assoc($dbResult);
    if (!$result) {
        return 0;
    }

    $created = strtotime($result['Created']);
    $curDate = strtotime(date('Y-m-d H:i:s'));
    $diff = $curDate - $created;

    $years = floor($diff / (365 * 60 * 60 * 24));

    return (int) $years;
}

function getUserActivityRange($user, &$firstLogin, &$lastLogin): bool
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

function getUserPageInfo(&$user, &$libraryOut, $numGames, $numRecentAchievements, $localUser): void
{
    sanitize_sql_inputs($user, $localUser);

    getAccountDetails($user, $userInfo);

    if (!$userInfo) {
        return;
    }

    $libraryOut = [];
    // getUserActivityRange($user, $firstLogin, $lastLogin);
    // $libraryOut['MemberSince'] = $firstLogin;
    // $libraryOut['LastLogin'] = $lastLogin;

    $libraryOut['RecentlyPlayedCount'] = getRecentlyPlayedGames($user, 0, $numGames, $recentlyPlayedData);
    $libraryOut['RecentlyPlayed'] = $recentlyPlayedData;
    $libraryOut['MemberSince'] = $userInfo['Created'];
    $libraryOut['LastActivity'] = $userInfo['LastLogin'];
    $libraryOut['RichPresenceMsg'] = empty($userInfo['RichPresenceMsg']) || $userInfo['RichPresenceMsg'] === 'Unknown' ? null : $userInfo['RichPresenceMsg'];
    $libraryOut['LastGameID'] = $userInfo['LastGameID'];
    if ($userInfo['LastGameID']) {
        $libraryOut['LastGame'] = getGameData($userInfo['LastGameID']);
    }
    $libraryOut['ContribCount'] = $userInfo['ContribCount'];
    $libraryOut['ContribYield'] = $userInfo['ContribYield'];
    $libraryOut['TotalPoints'] = $userInfo['RAPoints'];
    $libraryOut['TotalSoftcorePoints'] = $userInfo['RASoftcorePoints'];
    $libraryOut['TotalTruePoints'] = $userInfo['TrueRAPoints'];
    $libraryOut['Permissions'] = $userInfo['Permissions'];
    $libraryOut['Untracked'] = $userInfo['Untracked'];
    $libraryOut['ID'] = $userInfo['ID'];
    $libraryOut['UserWallActive'] = $userInfo['UserWallActive'];
    $libraryOut['Motto'] = $userInfo['Motto'];

    $libraryOut['Rank'] = getUserRank($user); // ANOTHER call... can't we cache this?

    $numRecentlyPlayed = is_countable($recentlyPlayedData) ? count($recentlyPlayedData) : 0;

    if ($numRecentlyPlayed > 0) {
        $gameIDsCSV = $recentlyPlayedData[0]['GameID'];

        for ($i = 1; $i < $numRecentlyPlayed; $i++) {
            $gameIDsCSV .= ", " . $recentlyPlayedData[$i]['GameID'];
        }

        getUserProgress($user, $gameIDsCSV, $awardedData);

        $libraryOut['Awarded'] = $awardedData;

        getUsersRecentAwardedForGames($user, $gameIDsCSV, $numRecentAchievements, $achievementData);

        $libraryOut['RecentAchievements'] = $achievementData;
    }
}

function getControlPanelUserInfo($user, &$libraryOut): bool
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

function getUserListByPerms($sortBy, $offset, $count, &$dataOut, $requestedBy, &$perms = null, $showUntracked = false): int
{
    sanitize_sql_inputs($offset, $count, $requestedBy, $perms);
    settype($offset, 'integer');
    settype($count, 'integer');
    settype($showUntracked, 'boolean');

    $whereQuery = null;
    $permsFilter = null;

    settype($perms, 'integer');
    if ($perms >= Permissions::Spam && $perms <= Permissions::Unregistered || $perms == Permissions::JuniorDeveloper) {
        $permsFilter = "ua.Permissions = $perms ";
    } elseif ($perms >= Permissions::Registered && $perms <= Permissions::Admin) {
        $permsFilter = "ua.Permissions >= $perms ";
    } else {
        if ($showUntracked) { // if reach this point, show only untracked users
            $whereQuery = "WHERE ua.Untracked ";
        } else { // perms invalid and do not show untracked? get outta here!
            return 0;
        }
    }

    if ($showUntracked) {
        if ($whereQuery == null) {
            $whereQuery = "WHERE $permsFilter ";
        }
    } else {
        $whereQuery = "WHERE ( !ua.Untracked || ua.User = \"$requestedBy\" ) AND $permsFilter";
    }

    settype($sortBy, 'integer');
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
                (SELECT COUNT(*) AS NumAwarded FROM Awarded AS aw WHERE aw.User = ua.User) NumAwarded
                FROM UserAccounts AS ua
                $whereQuery
                ORDER BY $orderBy
                LIMIT $offset, $count";

    $numFound = 0;
    $dataOut = [];
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$numFound] = $db_entry;
            $numFound++;
        }
    } else {
        log_sql_fail();
    }

    return $numFound;
}

function GetDeveloperStatsFull($count, $sortBy, $devFilter = 7): array
{
    sanitize_sql_inputs($count, $sortBy, $devFilter);
    settype($sortBy, 'integer');
    settype($count, 'integer');
    settype($devFilter, 'integer');

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
        4 => "TicketRatio DESC",
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
        COUNT(tick.ID)/COUNT(ach.ID) AS TicketRatio,
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
    WHERE
        ContribCount > 0 AND ContribYield > 0
        $stateCond
    GROUP BY
        ua.User
    ORDER BY
        $order,
        OpenTickets ASC";
    // LIMIT 0, $count";

    $db = getMysqliConnection();
    $dbResult = mysqli_query($db, $query);

    $retVal = [];
    if ($dbResult !== false) {
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $nextData;
        }
    }

    return $retVal;
}

function GetUserFields($username, $fields): ?array
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
 * Returns the information displayed in the usercard.
 */
function getUserCardData($user, &$userCardInfo): void
{
    getAccountDetails($user, $userInfo);

    if (!$userInfo) {
        $userCardInfo = null;

        return;
    }

    // getUserActivityRange($user, $firstLogin, $lastLogin);
    $userCardInfo = [];
    $userCardInfo['User'] = $userInfo['User'];
    $userCardInfo['HardcorePoints'] = $userInfo['RAPoints'];
    $userCardInfo['SoftcorePoints'] = $userInfo['RASoftcorePoints'];
    $userCardInfo['TotalTruePoints'] = $userInfo['TrueRAPoints'];
    $userCardInfo['Permissions'] = $userInfo['Permissions'];
    $userCardInfo['Motto'] = $userInfo['Motto'];
    $userCardInfo['Untracked'] = $userInfo['Untracked'];
    $userCardInfo['LastActivity'] = $userInfo['LastLogin'];
    $userCardInfo['MemberSince'] = $userInfo['Created'];
}

function attributeDevelopmentAuthor($author, $points): void
{
    sanitize_sql_inputs($author, $points);

    $query = "SELECT ContribCount, ContribYield FROM UserAccounts WHERE User = '$author'";
    $dbResult = s_mysql_query($query);
    $oldResults = mysqli_fetch_assoc($dbResult);
    if (!$oldResults) {
        // could not find a record for the author, nothing to update
        return;
    }

    $oldContribCount = (int) $oldResults['ContribCount'];
    $oldContribYield = (int) $oldResults['ContribYield'];

    // Update the fact that this author made an achievement that just got earned.
    $query = "UPDATE UserAccounts AS ua
              SET ua.ContribCount = ua.ContribCount+1, ua.ContribYield = ua.ContribYield + $points
              WHERE ua.User = '$author'";

    $dbResult = s_mysql_query($query);

    if (!$dbResult) {
        log_sql_fail();

        return;
    }

    for ($i = 0; $i < count(AwardThreshold::DEVELOPER_COUNT_BOUNDARIES); $i++) {
        if ($oldContribCount < AwardThreshold::DEVELOPER_COUNT_BOUNDARIES[$i] && $oldContribCount + 1 >= AwardThreshold::DEVELOPER_COUNT_BOUNDARIES[$i]) {
            // This developer has arrived at this point boundary!
            AddSiteAward($author, 2, $i);
        }
    }
    for ($i = 0; $i < count(AwardThreshold::DEVELOPER_POINT_BOUNDARIES); $i++) {
        if ($oldContribYield < AwardThreshold::DEVELOPER_POINT_BOUNDARIES[$i] && $oldContribYield + $points >= AwardThreshold::DEVELOPER_POINT_BOUNDARIES[$i]) {
            // This developer is newly above this point boundary!
            AddSiteAward($author, 3, $i);
        }
    }
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
