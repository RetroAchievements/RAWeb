<?php

use RA\ActivityType;
use RA\ArticleType;
use RA\AwardThreshold;
use RA\Permissions;
use RA\TicketState;

function generateEmailValidationString($user): ?string
{
    $emailCookie = rand_string(16);
    $expiry = date('Y-m-d', time() + 60 * 60 * 24 * 7);

    sanitize_sql_inputs($user);

    $query = "INSERT INTO EmailConfirmations (User, EmailCookie, Expires) VALUES( '$user', '$emailCookie', '$expiry' )";
    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();

        return null;
    }

    // Clear permissions til they validate their email.
    SetAccountPermissionsJSON('Server', Permissions::Admin, $user, Permissions::Unregistered);

    return $emailCookie;
}

function SetAccountPermissionsJSON($actingUser, $actingUserPermissions, $targetUser, $targetUserNewPermissions): array
{
    $retVal = [];
    sanitize_sql_inputs($actingUser, $targetUser, $targetUserNewPermissions);
    settype($targetUserNewPermissions, 'integer');

    if (!getAccountDetails($targetUser, $targetUserData)) {
        $retVal['Success'] = false;
        $retVal['Error'] = "$targetUser not found";
    }

    $targetUserCurrentPermissions = $targetUserData['Permissions'];

    $retVal = [
        'DestUser' => $targetUser,
        'DestPrevPermissions' => $targetUserCurrentPermissions,
        'NewPermissions' => $targetUserNewPermissions,
    ];

    $permissionChangeAllowed = true;

    // only admins can change permissions
    if ($actingUserPermissions < Permissions::Admin) {
        $permissionChangeAllowed = false;
    }

    // do not act on users on same or above level
    if ($targetUserCurrentPermissions >= $actingUserPermissions) {
        $permissionChangeAllowed = false;
    }

    // do not allow to set role to same or above level
    if ($targetUserNewPermissions >= $actingUserPermissions) {
        $permissionChangeAllowed = false;
    }

    if (!$permissionChangeAllowed) {
        $retVal['Success'] = false;
        $retVal['Error'] = "$actingUser ($actingUserPermissions) is trying to set $targetUser ($targetUserCurrentPermissions) to $targetUserNewPermissions??! Not allowed!";

        return $retVal;
    }

    $query = "UPDATE UserAccounts SET Permissions = $targetUserNewPermissions, Updated=NOW() WHERE User='$targetUser'";
    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        $retVal['Success'] = false;
        $retVal['Error'] = "$actingUser ($actingUserPermissions) is trying to set $targetUser ($targetUserCurrentPermissions) to $targetUserNewPermissions??! Cannot find user: '$targetUser'!";

        return $retVal;
    }

    if ($targetUserNewPermissions < Permissions::Unregistered) {
        banAccountByUsername($targetUser, $targetUserNewPermissions);
    }

    $retVal['Success'] = true;

    addArticleComment('Server', ArticleType::UserModeration, $targetUserData['ID'],
        $actingUser . ' set account type to ' . PermissionsToString($targetUserNewPermissions)
    );

    return $retVal;
}

function removeAvatar($user): void
{
    /**
     * remove avatar - replaced by default content
     */
    $avatarFile = rtrim(getenv('DOC_ROOT'), '/') . '/public/UserPic/' . $user . '.png';
    if (file_exists($avatarFile)) {
        unlink($avatarFile);
    }
    if (!filter_var(getenv('RA_AVATAR_FALLBACK'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
        $defaultAvatarFile = rtrim(getenv('DOC_ROOT'), '/') . '/public/UserPic/_User.png';
        copy($defaultAvatarFile, $avatarFile);
    }
}

function setAccountForumPostAuth($sourceUser, $sourcePermissions, $user, bool $authorize): bool
{
    sanitize_sql_inputs($user, $authorize);

    // $sourceUser is setting $user's forum post permissions.

    if (!$authorize) {
        // This user is a spam user: remove all their posts and set their account as banned.
        $query = "UPDATE UserAccounts SET ManuallyVerified = 0, Updated=NOW() WHERE User='$user'";
        $dbResult = s_mysql_query($query);
        if (!$dbResult) {
            return false;
        }

        // Also ban the spammy user!
        RemoveUnauthorisedForumPosts($user);

        SetAccountPermissionsJSON($sourceUser, $sourcePermissions, $user, Permissions::Spam);

        return true;
    }

    $query = "UPDATE UserAccounts SET ManuallyVerified = 1, Updated=NOW() WHERE User='$user'";
    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return false;
    }
    AuthoriseAllForumPosts($user);

    if (getAccountDetails($user, $userData)) {
        addArticleComment('Server', ArticleType::UserModeration, $userData['ID'],
            $sourceUser . ' authorized user\'s forum posts'
        );
    }

    // SUCCESS! Upgraded $user to allow forum posts, authorised by $sourceUser ($sourcePermissions)
    return true;
}

function validateEmailValidationString($emailCookie, &$user): bool
{
    sanitize_sql_inputs($emailCookie);

    $query = "SELECT * FROM EmailConfirmations WHERE EmailCookie='$emailCookie'";
    $dbResult = s_mysql_query($query);

    if (!$dbResult) {
        log_sql_fail();

        return false;
    }

    if (mysqli_num_rows($dbResult) == 1) {
        $data = mysqli_fetch_assoc($dbResult);
        $user = $data['User'];

        if (getUserPermissions($user) != Permissions::Unregistered) {
            return false;
        }

        $query = "DELETE FROM EmailConfirmations WHERE User='$user'";
        $dbResult = s_mysql_query($query);
        if (!$dbResult) {
            log_sql_fail();

            return false;
        }

        $response = SetAccountPermissionsJSON('Server', Permissions::Admin, $user, Permissions::Registered);
        if ($response['Success']) {
            static_addnewregistereduser($user);
            generateAPIKey($user);

            // SUCCESS: validated email address for $user
            return true;
        }
    }

    return false;
}

function generateCookie($user, &$cookie): bool
{
    if (empty($user)) {
        return false;
    }

    sanitize_sql_inputs($user);

    // while unlikely, it is imperative that this value is unique
    do {
        $cookie = rand_string(96);

        $query = "UPDATE UserAccounts SET cookie='$cookie', Updated=NOW() WHERE User='$user'";
        $result = s_mysql_query($query);
        if ($result === false) {
            return false;
        }

        $query = "SELECT count(*) AS Count FROM UserAccounts WHERE cookie='$cookie'";
        $result = s_mysql_query($query);
        if ($result === false) {
            return false;
        }

        $row = mysqli_fetch_array($result);
    } while ($row['Count'] > 1);

    $expDays = 7;
    $expiry = time() + 60 * 60 * 24 * $expDays;
    RA_SetCookie('RA_Cookie', $cookie, $expiry, true);

    return true;
}

function generateAppToken($user, &$tokenOut): bool
{
    if (empty($user)) {
        return false;
    }
    sanitize_sql_inputs($user);
    $newToken = rand_string(16);

    $expDays = 14;
    $expiryStr = date("Y-m-d H:i:s", (time() + 60 * 60 * 24 * $expDays));
    $query = "UPDATE UserAccounts SET appToken='$newToken', appTokenExpiry='$expiryStr', Updated=NOW() WHERE User='$user'";
    $result = s_mysql_query($query);
    if ($result !== false) {
        $tokenOut = $newToken;

        return true;
    } else {
        return false;
    }
}

function loginApp($user, $pass, $token): array
{
    sanitize_sql_inputs($user, $token);

    $query = null;
    $response = [];

    $passwordProvided = (isset($pass) && mb_strlen($pass) >= 1);
    $tokenProvided = (isset($token) && mb_strlen($token) >= 1);

    if (!isset($user) || $user == false || mb_strlen($user) < 2) {
        // username failed: empty user
    } else {
        if ($passwordProvided) {
            // Password provided, validate it
            if (authenticateFromPassword($user, $pass)) {
                $query = "SELECT RAPoints, Permissions, appToken FROM UserAccounts WHERE User='$user'";
            }
        } elseif ($tokenProvided) {
            // Token provided, look for match
            $query = "SELECT RAPoints, Permissions, appToken, appTokenExpiry FROM UserAccounts WHERE User='$user' AND appToken='$token'";
        }
    }

    if (!$query) {
        $response['Success'] = false;
        $response['Error'] = "Invalid User/Password combination. Please try again";

        return $response;
    }

    $dbResult = s_mysql_query($query);

    if (!$dbResult) {
        $response['Success'] = false;
        $response['Error'] = "Invalid User/Password combination. Please try again";

        return $response;
    }

    $data = mysqli_fetch_assoc($dbResult);
    if ($data !== false && mysqli_num_rows($dbResult) == 1) {
        // Test for expired tokens!
        if ($tokenProvided) {
            $expiry = $data['appTokenExpiry'];
            if (time() > strtotime($expiry)) {
                generateAppToken($user, $token);
                // Expired!
                $response['Success'] = false;
                $response['Error'] = "Automatic login failed (token expired), please login manually";

                return $response;
            }
        }

        if (mb_strlen($data['appToken']) !== 16) {   // Generate if new
            generateAppToken($user, $tokenInOut);
        } else {
            // Return old token if not
            $token = $data['appToken'];

            // Update app token expiry now anyway
            $expDays = 14;
            $expiryStr = date("Y-m-d H:i:s", (time() + 60 * 60 * 24 * $expDays));
            $query = "UPDATE UserAccounts SET appTokenExpiry='$expiryStr' WHERE User='$user'";
            s_mysql_query($query);
        }

        postActivity($user, ActivityType::Login, "");

        $response['Success'] = true;
        $response['User'] = $user;
        $response['Token'] = $token;
        $response['Score'] = $data['RAPoints'];
        settype($response['Score'], "integer");
        $response['Messages'] = GetMessageCount($user, $totalMessageCount);
        $response['Permissions'] = $data['Permissions'];
        settype($response['Permissions'], "integer");
        $response['AccountType'] = PermissionsToString($response['Permissions']);
    } else {
        $response['Success'] = false;
        $response['Error'] = "Invalid User/Password combination. Please try again";
    }

    return $response;
}

function GetUserData($user): ?array
{
    sanitize_sql_inputs($user);

    $query = "SELECT * FROM UserAccounts WHERE User='$user'";
    $dbResult = s_mysql_query($query);

    if (!$dbResult || mysqli_num_rows($dbResult) != 1) {
        log_sql_fail();

        // failed: Achievement $id doesn't exist!
        return null;
    } else {
        return mysqli_fetch_assoc($dbResult);
    }
}

function getAccountDetails(&$user, &$dataOut): bool
{
    if (!isset($user) || mb_strlen($user) < 2) {
        return false;
    }

    sanitize_sql_inputs($user);

    $query = "SELECT ID, User, EmailAddress, Permissions, RAPoints, TrueRAPoints,
                     cookie, websitePrefs, UnreadMessageCount, Motto, UserWallActive,
                     fbUser, fbPrefs, APIKey, ContribCount, ContribYield,
                     RichPresenceMsg, LastGameID, LastLogin, LastActivityID,
                     Created, DeleteRequested, Untracked
                FROM UserAccounts
                WHERE User='$user'
                AND Deleted IS NULL";

    $dbResult = s_mysql_query($query);
    if (!$dbResult || mysqli_num_rows($dbResult) !== 1) {
        return false;
    } else {
        $dataOut = mysqli_fetch_array($dbResult);
        $user = $dataOut['User'];    // Fix case!

        return true;
    }
}

function getAccountDetailsFromCookie(?string $cookie): ?array
{
    if (empty($cookie)) {
        return null;
    }

    sanitize_sql_inputs($cookie);

    // ID, User, and Permissions are used to identify the user and their permissions
    // DeleteRequested is used to show the "Your account is marked to be deleted" banner
    // RAPoints, TrueRAPoints, and UnreadMessageCount are used for the logged-in user area
    // websitePrefs allows pages to enable/disable functionality
    $query = "SELECT ID, User, Permissions, DeleteRequested,
                     RAPoints, TrueRAPoints, UnreadMessageCount,
                     websitePrefs
                FROM UserAccounts
                WHERE cookie='$cookie'
                AND Deleted IS NULL";

    $dbResult = s_mysql_query($query);
    if (!$dbResult || mysqli_num_rows($dbResult) !== 1) {
        return null;
    }

    return mysqli_fetch_array($dbResult) ?: null;
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

        return (string) $data['User'];
    }

    return "";
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
        ORDER BY ach.ID ASC, aw.HardcoreMode ASC ";

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

function getTopUsersByScore($count, &$dataOut, $ofFriend = null): int
{
    sanitize_sql_inputs($count, $ofFriend);
    settype($count, 'integer');

    if ($count > 10) {
        $count = 10;
    }

    $subquery = "WHERE !ua.Untracked";
    if (isset($ofFriend)) {
        // $subquery = "WHERE ua.User IN ( SELECT f.Friend FROM Friends AS f WHERE f.User = '$ofFriend' )
        // OR ua.User = '$ofFriend' ";
        // Only users whom I have added:
        $subquery = "WHERE !ua.Untracked AND ua.User IN ( SELECT f.Friend FROM Friends AS f WHERE f.User = '$ofFriend' AND f.Friendship = 1 )";
    }

    $query = "SELECT User, RAPoints, TrueRAPoints
              FROM UserAccounts AS ua
              $subquery
              ORDER BY RAPoints DESC 
              LIMIT 0, $count ";

    $dbResult = s_mysql_query($query);

    if (!$dbResult || mysqli_num_rows($dbResult) == 0) {
        // This is acceptible if the user doesn't have any friends!
        return 0;
    } else {
        $i = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            // $dataOut[$i][0] = $db_entry["ID"];
            $dataOut[$i][1] = $db_entry["User"];
            $dataOut[$i][2] = $db_entry["RAPoints"];
            $dataOut[$i][3] = $db_entry["TrueRAPoints"];
            $i++;
        }

        return $i;
    }
}

/**
 * Gets the number of friends for the input user.
 */
function getFriendCount(string $user): ?int
{
    sanitize_sql_inputs($user);

    $query = "SELECT COUNT(*) AS FriendCount
              FROM Friends
              WHERE User LIKE '$user'
              AND Friendship = 1";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        return (int) mysqli_fetch_assoc($dbResult)['FriendCount'];
    } else {
        return null;
    }
}

function getUserForumPostAuth($user): bool
{
    sanitize_sql_inputs($user);

    $query = "SELECT uc.ManuallyVerified FROM UserAccounts AS uc WHERE uc.User = '$user'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);

        return (bool) $data['ManuallyVerified'];
    } else {
        log_sql_fail();

        return $user;
    }
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

function GetScore($user): int
{
    sanitize_sql_inputs($user);

    $query = "SELECT ua.RAPoints
              FROM UserAccounts AS ua
              WHERE ua.User='$user'";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $result = mysqli_fetch_assoc($dbResult);
        if ($result) {
            return (int) $result['RAPoints'];
        }
    }

    return 0;
}

/**
 * Gets the account age in years for the input user.
 */
function getAge(string $user): int
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

/**
 * Gets the points or retro points rank of the user.
 */
function getUserRank(string $user, int $type = 0): ?int
{
    sanitize_sql_inputs($user);

    // 0 for points rank, anything else for retro points rank
    if ($type == 0) {
        $joinCond = "RIGHT JOIN UserAccounts AS ua2 ON ua.RAPoints < ua2.RAPoints AND NOT ua2.Untracked";
    } else {
        $joinCond = "RIGHT JOIN UserAccounts AS ua2 ON ua.TrueRAPoints < ua2.TrueRAPoints AND NOT ua2.Untracked";
    }

    $query = "SELECT ( COUNT(*) + 1 ) AS UserRank, ua.Untracked
                FROM UserAccounts AS ua
                $joinCond
                WHERE ua.User = '$user'";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();

        return null;
    }

    $data = mysqli_fetch_assoc($dbResult);
    if ($data['Untracked']) {
        return null;
    }

    return (int) $data['UserRank'];
}

function countRankedUsers(): int
{
    $query = "
        SELECT COUNT(*) AS count
        FROM UserAccounts
        WHERE RAPoints >= " . MIN_POINTS . "
          AND NOT Untracked
    ";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return 0;
    }

    return (int) mysqli_fetch_assoc($dbResult)['count'];
}

function updateAchievementVote($achID, $posDiff, $negDiff): bool
{
    sanitize_sql_inputs($achID, $posDiff, $negDiff);

    // Tell achievement $achID that it's vote count has been changed by $posDiff and $negDiff

    $query = "UPDATE Achievements SET VotesPos=VotesPos+$posDiff, VotesNeg=VotesNeg+$negDiff, Updated=NOW() WHERE ID=$achID";
    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();

        return false;
    }

    return true;
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

function getUserProgress($user, $gameIDsCSV, &$dataOut): ?int
{
    if (empty($gameIDsCSV) || !isValidUsername($user)) {
        return null;
    }
    sanitize_sql_inputs($user);

    // Create null entries so that we pass 'something' back.
    $gameIDsArray = explode(',', $gameIDsCSV);
    $gameIDs = [];
    foreach ($gameIDsArray as $gameID) {
        settype($gameID, "integer");
        $dataOut[$gameID]['NumPossibleAchievements'] = 0;
        $dataOut[$gameID]['PossibleScore'] = 0;
        $dataOut[$gameID]['NumAchieved'] = 0;
        $dataOut[$gameID]['ScoreAchieved'] = 0;
        $dataOut[$gameID]['NumAchievedHardcore'] = 0;
        $dataOut[$gameID]['ScoreAchievedHardcore'] = 0;
        $gameIDs[] = $gameID;
    }
    $gameIDs = implode(',', $gameIDs);

    // Count num possible achievements
    $query = "SELECT GameID, COUNT(*) AS AchCount, SUM(ach.Points) AS PointCount FROM Achievements AS ach
              WHERE ach.Flags = 3 AND ach.GameID IN ( $gameIDs )
              GROUP BY ach.GameID
              HAVING COUNT(*)>0 ";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return 0;
    }

    while ($data = mysqli_fetch_assoc($dbResult)) {
        $dataOut[$data['GameID']]['NumPossibleAchievements'] = $data['AchCount'];
        $dataOut[$data['GameID']]['PossibleScore'] = $data['PointCount'];
        $dataOut[$data['GameID']]['NumAchieved'] = 0;
        $dataOut[$data['GameID']]['ScoreAchieved'] = 0;
        $dataOut[$data['GameID']]['NumAchievedHardcore'] = 0;
        $dataOut[$data['GameID']]['ScoreAchievedHardcore'] = 0;
    }

    // Foreach return value from this, cross reference with 'earned' achievements. If not found, assume 0.
    // Count earned achievements
    $query = "SELECT GameID, COUNT(*) AS AchCount, SUM( ach.Points ) AS PointCount, aw.HardcoreMode
              FROM Awarded AS aw
              LEFT JOIN Achievements AS ach ON aw.AchievementID = ach.ID
              WHERE ach.GameID IN ( $gameIDsCSV ) AND ach.Flags = 3 AND aw.User = '$user'
              GROUP BY aw.HardcoreMode, ach.GameID";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return 0;
    }

    while ($data = mysqli_fetch_assoc($dbResult)) {
        if ($data['HardcoreMode'] == 0) {
            $dataOut[$data['GameID']]['NumAchieved'] = $data['AchCount'];
            $dataOut[$data['GameID']]['ScoreAchieved'] = $data['PointCount'];
        } else {
            $dataOut[$data['GameID']]['NumAchievedHardcore'] = $data['AchCount'];
            $dataOut[$data['GameID']]['ScoreAchievedHardcore'] = $data['PointCount'];
        }
    }

    return 0;
}

function GetAllUserProgress($user, $consoleID): array
{
    $retVal = [];
    sanitize_sql_inputs($user, $consoleID);
    settype($consoleID, 'integer');

    // Title,
    $query = "SELECT ID, IFNULL( AchCounts.NumAch, 0 ) AS NumAch, IFNULL( MyAwards.NumIAchieved, 0 ) AS Earned, IFNULL( MyAwardsHC.NumIAchieved, 0 ) AS HCEarned
            FROM GameData AS gd
            LEFT JOIN (
                SELECT COUNT(ach.ID) AS NumAch, GameID
                FROM Achievements AS ach
                GROUP BY ach.GameID ) AchCounts ON AchCounts.GameID = gd.ID

            LEFT JOIN (
                SELECT gd.ID AS GameID, COUNT(*) AS NumIAchieved
                FROM Awarded AS aw
                LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
                WHERE aw.User = '$user' AND aw.HardcoreMode = 0
                GROUP BY gd.ID ) MyAwards ON MyAwards.GameID = gd.ID

            LEFT JOIN (
                SELECT gd.ID AS GameID, COUNT(*) AS NumIAchieved
                FROM Awarded AS aw
                LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
                WHERE aw.User = '$user' AND aw.HardcoreMode = 1
                GROUP BY gd.ID ) MyAwardsHC ON MyAwardsHC.GameID = gd.ID

            WHERE NumAch > 0 && gd.ConsoleID = $consoleID
            ORDER BY ID ASC";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            // Auto:
            // $retVal[] = $nextData;
            // Manual:
            $nextID = $nextData['ID'];
            unset($nextData['ID']);

            settype($nextData['NumAch'], 'integer');
            settype($nextData['Earned'], 'integer');
            settype($nextData['HCEarned'], 'integer');

            $retVal[$nextID] = $nextData;
        }
    }

    return $retVal;
}

function getUsersGameList($user, &$dataOut): int
{
    sanitize_sql_inputs($user);

    $query = "SELECT gd.Title, c.Name AS ConsoleName, gd.ID, COUNT(AchievementID) AS NumAchieved
        FROM Awarded AS aw
        LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
        LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
        LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
        LEFT JOIN ( SELECT ach1.GameID AS GameIDInner, ach1.ID, COUNT(ach1.ID) AS TotalAch FROM Achievements AS ach1 GROUP BY GameID ) AS gt ON gt.GameIDInner = gd.ID
        WHERE aw.User = '$user'
        GROUP BY gd.ID";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return 0;
    }

    $gamelistCSV = '0';

    while ($nextData = mysqli_fetch_assoc($dbResult)) {
        $dataOut[$nextData['ID']] = $nextData;
        $gamelistCSV .= ', ' . $nextData['ID'];
    }

    // Get totals:
    $query = "SELECT ach.GameID, gd.Title, COUNT(ach.ID) AS NumAchievements
            FROM Achievements AS ach
            LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
            WHERE ach.Flags = 3 AND ach.GameID IN ( $gamelistCSV )
            GROUP BY ach.GameID ";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return 0;
    }

    $i = 0;
    while ($nextData = mysqli_fetch_assoc($dbResult)) {
        $dataOut[$nextData['GameID']]['Title'] = $nextData['Title'];
        $dataOut[$nextData['GameID']]['NumAchievements'] = $nextData['NumAchievements'];
        $i++;
    }

    return $i;
}

function getUsersRecentAwardedForGames($user, $gameIDsCSV, $numAchievements, &$dataOut): void
{
    sanitize_sql_inputs($user, $numAchievements);
    settype($numAchievements, 'integer');

    if (empty($gameIDsCSV)) {
        return;
    }

    $gameIDsArray = explode(',', $gameIDsCSV);

    $gameIDs = [];
    foreach ($gameIDsArray as $gameID) {
        settype($gameID, "integer");
        $gameIDs[] = $gameID;
    }
    $gameIDs = implode(',', $gameIDs);

    $limit = ($numAchievements == 0) ? 5000 : $numAchievements;

    // TODO: because of the "ORDER BY HardcoreAchieved", this query only returns non-hardcore
    //       unlocks if the user has more than $limit unlocks. Note that $limit appears to be
    //       default (5000) for all use cases except API_GetUserSummary
    $query = "SELECT ach.ID, ach.GameID, gd.Title AS GameTitle, ach.Title, ach.Description, ach.Points, ach.BadgeName, (!ISNULL(aw.User)) AS IsAwarded, aw.Date AS DateAwarded, (aw.HardcoreMode) AS HardcoreAchieved
              FROM Achievements AS ach
              LEFT OUTER JOIN Awarded AS aw ON aw.User = '$user' AND aw.AchievementID = ach.ID
              LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
              WHERE ach.Flags = 3 AND ach.GameID IN ( $gameIDs )
              ORDER BY IsAwarded DESC, HardcoreAchieved ASC, DateAwarded DESC, ach.DisplayOrder ASC, ach.ID ASC
              LIMIT $limit";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$db_entry['GameID']][$db_entry['ID']] = $db_entry;
        }
    }
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

    $libraryOut['Friendship'] = 0;
    $libraryOut['FriendReciprocation'] = 0;

    if (isset($localUser) && ($localUser != $user)) {
        $query = "SELECT (f.User = '$localUser') AS Local, f.Friend, f.Friendship FROM Friends AS f
                  WHERE (f.User = '$localUser' && f.Friend = '$user')
                  UNION
                  SELECT (f.User = '$localUser') AS Local, f.Friend, f.Friendship FROM Friends AS f
                  WHERE (f.User = '$user' && f.Friend = '$localUser') ";

        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            while ($db_entry = mysqli_fetch_assoc($dbResult)) {
                if ($db_entry['Local'] == 1) {
                    $libraryOut['Friendship'] = $db_entry['Friendship'];
                } else { // if ( $db_entry['Local'] == 0 )
                    $libraryOut['FriendReciprocation'] = $db_entry['Friendship'];
                }
            }
        } else {
            log_sql_fail();
        }
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

function getUserPermissions(?string $user): int
{
    if ($user == null) {
        return 0;
    }

    sanitize_sql_inputs($user);

    $query = "SELECT Permissions FROM UserAccounts WHERE User='$user'";
    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();

        return 0;
    }

    $data = mysqli_fetch_assoc($dbResult);

    return (int) $data['Permissions'];
}

function getUsersCompletedGamesAndMax($user): array
{
    $retVal = [];

    if (!isValidUsername($user)) {
        return $retVal;
    }

    sanitize_sql_inputs($user);

    $requiredFlags = 3;
    $minAchievementsForCompletion = 5;

    $query = "SELECT gd.ID AS GameID, c.Name AS ConsoleName, c.ID AS ConsoleID, gd.ImageIcon, gd.Title, COUNT(ach.GameID) AS NumAwarded, inner1.MaxPossible, (COUNT(ach.GameID)/inner1.MaxPossible) AS PctWon, aw.HardcoreMode
        FROM Awarded AS aw
        LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
        LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
        LEFT JOIN
            ( SELECT COUNT(*) AS MaxPossible, ach1.GameID FROM Achievements AS ach1 WHERE Flags = $requiredFlags GROUP BY GameID )
            AS inner1 ON inner1.GameID = ach.GameID AND inner1.MaxPossible > $minAchievementsForCompletion
        LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
        WHERE aw.User='$user' AND ach.Flags = $requiredFlags
        GROUP BY ach.GameID, aw.HardcoreMode, gd.Title
        ORDER BY PctWon DESC, inner1.MaxPossible DESC, gd.Title ";

    global $db;
    $dbResult = mysqli_query($db, $query);

    $gamesFound = 0;
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[$gamesFound] = $db_entry;
            $gamesFound++;
        }
    }

    return $retVal;
}

function getUsersSiteAwards($user, $showHidden = false): array
{
    sanitize_sql_inputs($user);

    $retVal = [];

    if (!isValidUsername($user)) {
        return $retVal;
    }

    $hiddenQuery = "";
    if (!$showHidden) {
        $hiddenQuery = "AND saw.DisplayOrder > -1";
    }

    $query = "
    (
    SELECT UNIX_TIMESTAMP( saw.AwardDate ) as AwardedAt, saw.AwardType, saw.AwardData, saw.AwardDataExtra, saw.DisplayOrder, gd.Title, c.Name AS ConsoleName, gd.Flags, gd.ImageIcon
                  FROM SiteAwards AS saw
                  LEFT JOIN GameData AS gd ON ( gd.ID = saw.AwardData AND saw.AwardType = 1 )
                  LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
                  WHERE saw.AwardType = 1 AND saw.User = '$user' $hiddenQuery
                  GROUP BY saw.AwardType, saw.AwardData, saw.AwardDataExtra
    )
    UNION
    (
    SELECT UNIX_TIMESTAMP( saw.AwardDate ) as AwardedAt, saw.AwardType, MAX( saw.AwardData ), saw.AwardDataExtra, saw.DisplayOrder, NULL, NULL, NULL, NULL
                  FROM SiteAwards AS saw
                  WHERE saw.AwardType > 1 AND saw.User = '$user' $hiddenQuery
                  GROUP BY saw.AwardType

    )
    ORDER BY DisplayOrder, AwardedAt, AwardType, AwardDataExtra ASC";

    global $db;
    $dbResult = mysqli_query($db, $query);

    $numFound = 0;
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[$numFound] = $db_entry;
            $numFound++;
        }

        // Updated way to "squash" duplicate awards to work with the new site award ordering implementation
        $completedGames = [];
        $masteredGames = [];

        // Get a separate list of completed and mastered games
        for ($i = 0; $i < count($retVal); $i++) {
            if ($retVal[$i]['AwardType'] == 1 &&
                $retVal[$i]['AwardDataExtra'] == 1) {
                $masteredGames[] = $retVal[$i]['AwardData'];
            } elseif ($retVal[$i]['AwardType'] == 1 &&
                $retVal[$i]['AwardDataExtra'] == 0) {
                $completedGames[] = $retVal[$i]['AwardData'];
            }
        }

        // Get a single list of games both completed and mastered
        if (count($completedGames) > 0 && count($masteredGames) > 0) {
            $multiAwardGames = array_intersect($completedGames, $masteredGames);

            // For games that have been both completed and mastered, remove the completed entry from the award array.
            foreach ($multiAwardGames as $game) {
                $index = 0;
                foreach ($retVal as $award) {
                    if (isset($award['AwardData']) &&
                        $award['AwardData'] == $game &&
                        $award['AwardDataExtra'] == 0) {
                        $retVal[$index] = "";
                        break;
                    }
                    $index++;
                }
            }
        }

        // Remove blank indexes
        $retVal = array_values(array_filter($retVal));
    }

    return $retVal;
}

function AddSiteAward($user, $awardType, $data, $dataExtra = 0): void
{
    sanitize_sql_inputs($user, $awardType, $data, $dataExtra);
    settype($awardType, 'integer');
    // settype( $data, 'integer' );    // nullable
    settype($dataExtra, 'integer');

    $displayOrder = 0;
    $query = "SELECT MAX( DisplayOrder ) FROM SiteAwards WHERE User = '$user'";
    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();
    } else {
        $dbData = mysqli_fetch_assoc($dbResult);
        if (isset($dbData['MAX( DisplayOrder )'])) {
            $displayOrder = (int) $dbData['MAX( DisplayOrder )'] + 1;
        }
    }

    $query = "INSERT INTO SiteAwards (AwardDate, User, AwardType, AwardData, AwardDataExtra, DisplayOrder) 
                            VALUES( NOW(), '$user', '$awardType', '$data', '$dataExtra', '$displayOrder' ) ON DUPLICATE KEY UPDATE AwardDate = NOW()";
    global $db;
    mysqli_query($db, $query);
}

function GetDeveloperStats($count, $type)
{
    sanitize_sql_inputs($count);

    if ($type == 1) {
        $query = "SELECT ua.User as Author, ContribYield as NumCreated
                FROM UserAccounts AS ua
                WHERE ContribYield > 0
                ORDER BY ContribYield DESC
                LIMIT 0, $count";
    } elseif ($type == 2) {
        $query = "SELECT ua.User as Author, ContribCount as NumCreated
                FROM UserAccounts AS ua
                WHERE ContribCount > 0
                ORDER BY ContribCount DESC
                LIMIT 0, $count";
    } else {
        $query = "SELECT ach.Author, COUNT(*) as NumCreated
                FROM Achievements as ach
                WHERE ach.Flags = 3
                GROUP BY ach.Author
                ORDER BY NumCreated DESC
                LIMIT 0, $count";
    }

    $dbResult = s_mysql_query($query);

    $retVal = [];
    while ($db_entry = mysqli_fetch_assoc($dbResult)) {
        settype($db_entry['NumCreated'], 'integer');
        $retVal[] = $db_entry;
    }

    return $retVal;
}

function GetDeveloperStatsFull($count, $sortBy, $devFilter = 7)
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
        default => "Achievements DESC",
    };

    $query = "
    SELECT
        ua.User AS Author,
        Permissions,
        ContribCount,
        ContribYield,
        COUNT(DISTINCT(CASE WHEN ach.Flags = 3 THEN ach.ID ELSE NULL END)) AS Achievements,
        COUNT(tick.ID) AS OpenTickets,
        COUNT(tick.ID)/COUNT(ach.ID) AS TicketRatio,
        LastLogin
    FROM
        UserAccounts AS ua
    LEFT JOIN
        Achievements AS ach ON (ach.Author = ua.User AND ach.Flags IN (3, 5))
    LEFT JOIN
        Ticket AS tick ON (tick.AchievementID = ach.ID AND tick.ReportState IN (" . TicketState::Open . "," . TicketState::Request . "))
    WHERE
        ContribCount > 0 AND ContribYield > 0
        $stateCond
    GROUP BY
        ua.User
    ORDER BY
        $order,
        OpenTickets ASC";
    // LIMIT 0, $count";

    global $db;
    $dbResult = mysqli_query($db, $query);

    $retVal = [];
    if ($dbResult !== false) {
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $nextData;
        }
    }

    return $retVal;
}

function GetUserFields($username, $fields)
{
    sanitize_sql_inputs($username);

    $fieldsCSV = implode(",", $fields);
    $query = "SELECT $fieldsCSV FROM UserAccounts AS ua
              WHERE ua.User = '$username'";
    $dbResult = s_mysql_query($query);

    return mysqli_fetch_assoc($dbResult);
}

function HasPatreonBadge($usernameIn): bool
{
    sanitize_sql_inputs($usernameIn);

    $query = "SELECT * FROM SiteAwards AS sa "
        . "WHERE sa.AwardType = 6 AND sa.User = '$usernameIn'";

    $dbResult = s_mysql_query($query);

    return mysqli_num_rows($dbResult) > 0;
}

function SetPatreonSupporter($usernameIn, $enable)
{
    sanitize_sql_inputs($usernameIn);

    if ($enable) {
        AddSiteAward($usernameIn, 6, 0, 0);
    } else {
        $query = "DELETE FROM SiteAwards WHERE User = '$usernameIn' AND AwardType = '6'";
        s_mysql_query($query);
    }
}

function SetUserUntrackedStatus($usernameIn, $isUntracked)
{
    sanitize_sql_inputs($usernameIn, $isUntracked);

    $query = "UPDATE UserAccounts SET Untracked = $isUntracked, Updated=NOW() WHERE User = '$usernameIn'";
    s_mysql_query($query);
}

/**
 * Returns the information displayed in the usercard.
 *
 * @param string $user the user to get information for
 * @param array $userCardInfo information to be dispaled in the user card
 */
function getUserCardData($user, &$userCardInfo)
{
    getAccountDetails($user, $userInfo);

    if (!$userInfo) {
        $userCardInfo = null;

        return;
    }

    // getUserActivityRange($user, $firstLogin, $lastLogin);
    $userCardInfo = [];
    $userCardInfo['TotalPoints'] = $userInfo['RAPoints'];
    $userCardInfo['TotalTruePoints'] = $userInfo['TrueRAPoints'];
    $userCardInfo['Permissions'] = $userInfo['Permissions'];
    $userCardInfo['Motto'] = $userInfo['Motto'];
    $userCardInfo['Rank'] = getUserRank($user);
    $userCardInfo['Untracked'] = $userInfo['Untracked'];
    $userCardInfo['LastActivity'] = $userInfo['LastLogin'];
    $userCardInfo['MemberSince'] = $userInfo['Created'];
}

function recalcScore($user)
{
    sanitize_sql_inputs($user);

    $query = "UPDATE UserAccounts SET RAPoints = (
                SELECT SUM(ach.Points) FROM Awarded AS aw
                LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                WHERE aw.User = '$user' AND ach.Flags = 3
                ),
                TrueRAPoints = (
                SELECT SUM(ach.TrueRatio) FROM Awarded AS aw
                LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                WHERE aw.User = '$user' AND ach.Flags = 3
                )
              WHERE User = '$user' ";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return false;
    } else {
        // recalc'd $user's score as " . getScore($user) . ", OK!
        return true;
    }
}

function attributeDevelopmentAuthor($author, $points)
{
    sanitize_sql_inputs($author, $points);

    $query = "SELECT ContribCount, ContribYield FROM UserAccounts WHERE User = '$author'";
    $dbResult = s_mysql_query($query);
    $oldResults = mysqli_fetch_assoc($dbResult);
    $oldContribCount = (int) $oldResults['ContribCount'];
    $oldContribYield = (int) $oldResults['ContribYield'];

    // Update the fact that this author made an achievement that just got earned.
    $query = "UPDATE UserAccounts AS ua
              SET ua.ContribCount = ua.ContribCount+1, ua.ContribYield = ua.ContribYield + $points
              WHERE ua.User = '$author'";

    $dbResult = s_mysql_query($query);

    if (!$dbResult) {
        log_sql_fail();
    } else {
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
}

function recalculateDevelopmentContributions($user)
{
    sanitize_sql_inputs($user);

    // ##SD Should be rewritten using a single inner table... damnit!

    $query = "UPDATE UserAccounts AS ua
              SET ua.ContribCount = (
                      SELECT COUNT(*) AS achCount
                      FROM Awarded AS aw
                      LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                      WHERE aw.User != ach.Author AND ach.Author = '$user' AND ach.Flags = 3 ),
              ua.ContribYield = (
                      SELECT SUM(ach.Points) AS achPoints
                      FROM Awarded AS aw
                      LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                      WHERE aw.User != ach.Author AND ach.Author = '$user' AND ach.Flags = 3 )
              WHERE ua.User = '$user'";

    $dbResult = s_mysql_query($query);

    return !$dbResult;
}

/**
 * Gets completed and mastered counts for all users who have played the passed in games.
 */
function getMostAwardedUsers(array $gameIDs): array
{
    $retVal = [];
    $query = "SELECT ua.User,
              SUM(CASE WHEN AwardDataExtra LIKE '0' THEN 1 ELSE 0 END) AS Completed,
              SUM(CASE WHEN AwardDataExtra LIKE '1' THEN 1 ELSE 0 END) AS Mastered
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
              SUM(CASE WHEN AwardDataExtra LIKE '0' AND Untracked = 0 THEN 1 ELSE 0 END) AS Completed,
              SUM(CASE WHEN AwardDataExtra LIKE '1' AND Untracked = 0 THEN 1 ELSE 0 END) AS Mastered
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

function getDeleteDate($deleteRequested): string
{
    if (empty($deleteRequested)) {
        return '';
    }

    return date('Y-m-d', strtotime($deleteRequested) + 60 * 60 * 24 * 14);
}

function cancelDeleteRequest($username): bool
{
    getAccountDetails($username, $user);

    $query = "UPDATE UserAccounts u SET u.DeleteRequested = NULL WHERE u.User = '$username'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        addArticleComment('Server', ArticleType::UserModeration, $user['ID'],
            $username . ' canceled account deletion'
        );
    }

    return $dbResult !== false;
}

function deleteRequest($username, $date = null): bool
{
    getAccountDetails($username, $user);

    if ($user['DeleteRequested']) {
        return false;
    }

    // Cap permissions
    $permission = min($user['Permissions'], Permissions::Registered);

    $date ??= date('Y-m-d H:i:s');
    $query = "UPDATE UserAccounts u SET u.DeleteRequested = '$date', u.Permissions = $permission WHERE u.User = '$username'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        addArticleComment('Server', ArticleType::UserModeration, $user['ID'],
            $username . ' requested account deletion'
        );

        SendDeleteRequestEmail($username, $user['EmailAddress'], $date);
    }

    return $dbResult !== false;
}

function deleteOverdueUserAccounts(): void
{
    $threshold = date('Y-m-d 08:00:00', time() - 60 * 60 * 24 * 14);

    $query = "SELECT * FROM UserAccounts u WHERE u.DeleteRequested <= '$threshold' AND u.Deleted IS NULL ORDER BY u.DeleteRequested";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return;
    }

    foreach ($dbResult as $user) {
        clearAccountData($user);
    }
}

function clearAccountData($user): void
{
    global $db;

    $userId = $user['ID'];
    $username = $user['User'];

    echo "DELETING $username [$userId] ... ";

    if (empty($userId) || empty($username)) {
        echo "FAIL" . PHP_EOL;

        return;
    }

    $dbResult = s_mysql_query("DELETE FROM Activity WHERE User = '$username'");
    if (!$dbResult) {
        echo mysqli_error($db);
    }
    $dbResult = s_mysql_query("DELETE FROM Awarded WHERE User = '$username'");
    if (!$dbResult) {
        echo mysqli_error($db) . PHP_EOL;
    }
    $dbResult = s_mysql_query("DELETE FROM EmailConfirmations WHERE User = '$username'");
    if (!$dbResult) {
        echo mysqli_error($db) . PHP_EOL;
    }
    $dbResult = s_mysql_query("DELETE FROM Friends WHERE User = '$username' OR Friend = '$username'");
    if (!$dbResult) {
        echo mysqli_error($db) . PHP_EOL;
    }
    $dbResult = s_mysql_query("DELETE FROM Rating WHERE User = '$username'");
    if (!$dbResult) {
        echo mysqli_error($db) . PHP_EOL;
    }
    $dbResult = s_mysql_query("DELETE FROM SetRequest WHERE User = '$username'");
    if (!$dbResult) {
        echo mysqli_error($db) . PHP_EOL;
    }
    $dbResult = s_mysql_query("DELETE FROM SiteAwards WHERE User = '$username'");
    if (!$dbResult) {
        echo mysqli_error($db) . PHP_EOL;
    }
    $dbResult = s_mysql_query("DELETE FROM Subscription WHERE UserID = '$userId'");
    if (!$dbResult) {
        echo mysqli_error($db) . PHP_EOL;
    }

    // Cap permissions to 0 - negative values may stay
    $permission = min($user['Permissions'], 0);

    $dbResult = s_mysql_query("UPDATE UserAccounts u SET 
        u.Password = null, 
        u.SaltedPass = '', 
        u.EmailAddress = '', 
        u.Permissions = $permission, 
        u.RAPoints = 0,
        u.TrueRAPoints = null,
        u.fbUser = 0, 
        u.fbPrefs = null, 
        u.cookie = null, 
        u.appToken = null, 
        u.appTokenExpiry = null, 
        u.websitePrefs = 0, 
        u.LastLogin = null, 
        u.LastActivityID = 0, 
        u.Motto = '', 
        u.Untracked = 1, 
        u.ContribCount = 0, 
        u.ContribYield = 0,
        u.APIKey = null,
        u.UserWallActive = 0,
        u.LastGameID = 0,
        u.RichPresenceMsg = null,
        u.RichPresenceMsgDate = null,
        u.PasswordResetToken = null,
        u.Deleted = NOW()
        WHERE ID = '$userId'"
    );
    if (!$dbResult) {
        echo mysqli_error($db) . PHP_EOL;
    }

    removeAvatar($username);

    echo "SUCCESS" . PHP_EOL;
}

/**
 * APIKey doesn't have to be reset -> permission >= Registered
 */
function banAccountByUsername(string $username, $permissions): void
{
    global $db;

    echo "BANNING $username ... ";

    if (empty($username)) {
        echo "FAIL" . PHP_EOL;

        return;
    }

    $dbResult = s_mysql_query("UPDATE UserAccounts u SET 
        u.Password = null, 
        u.SaltedPass = '', 
        u.Permissions = $permissions, 
        u.fbUser = 0, 
        u.fbPrefs = null, 
        u.cookie = null, 
        u.appToken = null, 
        u.appTokenExpiry = null, 
        u.Motto = '', 
        u.Untracked = 1, 
        u.APIKey = null, 
        u.UserWallActive = 0, 
        u.RichPresenceMsg = null, 
        u.RichPresenceMsgDate = null, 
        u.PasswordResetToken = '' 
        WHERE u.User='$username'"
    );
    if (!$dbResult) {
        echo mysqli_error($db) . PHP_EOL;
    }

    removeAvatar($username);

    echo "SUCCESS" . PHP_EOL;
}
