<?php

use RA\ActivityType;
use RA\Permissions;

abstract class UserPref
{
    public const EmailOn_ActivityComment = 0;

    public const EmailOn_AchievementComment = 1;

    public const EmailOn_UserWallComment = 2;

    public const EmailOn_ForumReply = 3;

    public const EmailOn_AddFriend = 4;

    public const EmailOn_PrivateMessage = 5;

    public const EmailOn_Newsletter = 6;

    public const EmailOn_unused2 = 7;

    public const SiteMsgOn_ActivityComment = 8;

    public const SiteMsgOn_AchievementComment = 9;

    public const SiteMsgOn_UserWallComment = 10;

    public const SiteMsgOn_ForumReply = 11;

    public const SiteMsgOn_AddFriend = 12;
}

abstract class FBUserPref
{
    public const PostFBOn_EarnAchievement = 0;

    public const PostFBOn_CompleteGame = 1;

    public const PostFBOn_UploadAchievement = 2;
}

//////////////////////////////////////////////////////////////////////////////////////////
//    Accounts
//////////////////////////////////////////////////////////////////////////////////////////

function generateEmailValidationString($user)
{
    $emailCookie = rand_string(16);
    // $expiry = date('Y-m-d', time() + 60 * 60 * 24 * 7);
    $expiry = time() + 60 * 60 * 24 * 7;

    sanitize_sql_inputs($user);

    $query = "INSERT INTO EmailConfirmations VALUES( '$user', '$emailCookie', $expiry )";
    // log_sql($query);
    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        log_sql_fail();
        return false;
    }

    //    Clear permissions til they validate their email.
    SetAccountPermissionsJSON('Scott', Permissions::Admin, $user, 0);

    return $emailCookie;
}

function SetAccountPermissionsJSON($actingUser, $actingUserPermissions, $targetUser, $targetUserNewPermissions)
{
    sanitize_sql_inputs($actingUser, $targetUser, $targetUserNewPermissions);
    settype($targetUserNewPermissions, 'integer');

    $targetUserCurrentPermissions = getUserPermissions($targetUser);

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
    if ($dbResult == false) {
        $retVal['Success'] = false;
        $retVal['Error'] = "$actingUser ($actingUserPermissions) is trying to set $targetUser ($targetUserCurrentPermissions) to $targetUserNewPermissions??! Cannot find user: '$targetUser'!";
        return $retVal;
    }

    if ($targetUserNewPermissions < Permissions::Unregistered) {
        banAccountByUsername($targetUser, $targetUserNewPermissions);
    }

    $retVal['Success'] = true;

    return $retVal;
}

function removeAvatar($user)
{
    /**
     * remove avatar - replaced by default content
     */
    $avatarFile = rtrim(getenv('DOC_ROOT'), '/') . '/public/UserPic/' . $user . '.png';
    if (file_exists($avatarFile)) {
        unlink($avatarFile);
    }
    if (!getenv('RA_AVATAR_FALLBACK')) {
        $defaultAvatarFile = rtrim(getenv('DOC_ROOT'), '/') . '/public/UserPic/_User.png';
        copy($defaultAvatarFile, $avatarFile);
    }
}

function setAccountForumPostAuth($sourceUser, $sourcePermissions, $user, $permissions)
{
    sanitize_sql_inputs($user, $permissions);
    settype($permissions, 'integer');

    //    $sourceUser is setting $user's forum post permissions.

    if ($permissions == 0) {
        //    This user is a spam user: remove all their posts and set their account as banned.
        $query = "UPDATE UserAccounts SET ManuallyVerified = $permissions, Updated=NOW() WHERE User='$user'";
        // log_sql($query);
        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            //    Also ban the spammy user!
            RemoveUnauthorisedForumPosts($user);

            SetAccountPermissionsJSON($sourceUser, $sourcePermissions, $user, Permissions::Spam);
            return true;
        } else {
            //    Unrecognised  user
            // error_log(__FUNCTION__ . " failed: cannot update $user in UserAccounts??! $user, $permissions");
            return false;
        }
    } elseif ($permissions == 1) {
        $query = "UPDATE UserAccounts SET ManuallyVerified = $permissions, Updated=NOW() WHERE User='$user'";
        // log_sql($query);
        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            AuthoriseAllForumPosts($user);

            // error_log(__FUNCTION__ . " SUCCESS! Upgraded $user to allow forum posts, authorised by $sourceUser ($sourcePermissions)");
            return true;
        } else {
            //    Unrecognised  user
            // error_log(__FUNCTION__ . " failed: cannot update $user in UserAccounts??! $user, $permissions");
            return false;
        }
    } else { //    ?
        //    Unrecognised stuff
        // error_log(__FUNCTION__ . " failed: cannot update $user in UserAccounts??! $user, $permissions");
        return false;
    }
}

function validateEmailValidationString($emailCookie, &$user)
{
    sanitize_sql_inputs($emailCookie);

    $query = "SELECT * FROM EmailConfirmations WHERE EmailCookie='$emailCookie'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        if (mysqli_num_rows($dbResult) == 1) {
            $data = mysqli_fetch_assoc($dbResult);
            $user = $data['User'];

            if (getUserPermissions($user) != Permissions::Unregistered) {
                return false;
            }

            $query = "DELETE FROM EmailConfirmations WHERE User='$user'";
            // log_sql($query);
            $dbResult = s_mysql_query($query);
            if ($dbResult !== false) {
                $response = SetAccountPermissionsJSON('Scott', Permissions::Admin, $user, Permissions::Registered);
                if ($response['Success']) {
                    static_addnewregistereduser($user);
                    generateAPIKey($user);
                    // error_log(__FUNCTION__ . " SUCCESS: validated email address for $user");
                    return true;
                } else {
                    // error_log(__FUNCTION__ . " failed: cant set user's permissions to 1?? $user, $emailCookie - " . $response['Error']);
                    return false;
                }
            } else {
                log_sql_fail();
                // error_log(__FUNCTION__ . " failed: can't remove the email confirmation we just found??! $user, $emailCookie");
                return false;
            }
        } else {
            //    Unrecognised cookie or user
            // log_sql_fail();
            // error_log(__FUNCTION__ . " failed: $emailCookie num rows found:" . mysqli_num_rows($dbResult));
            return false;
        }
    } else {
        //    Unrecognised db query
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed: $emailCookie !$dbResult");
        return false;
    }
}

function generateCookie($user, &$cookie)
{
    if (!isset($user) || $user == false) {
        return false;
    }
    sanitize_sql_inputs($user);

    $cookie = rand_string(16);
    $query = "UPDATE UserAccounts SET cookie='$cookie', Updated=NOW() WHERE User='$user'";

    $result = s_mysql_query($query);
    $expDays = 7;
    $expiry = time() + 60 * 60 * 24 * $expDays;
    if ($result !== false) {
        RA_SetCookie('RA_User', $user, $expiry, false);
        RA_SetCookie('RA_Cookie', $cookie, $expiry, true);
        return true;
    } else {
        // error_log(__FUNCTION__ . " failed: cannot update DB: $query");
        RA_ClearCookie('RA_User');
        return false;
    }
}

function generateAppToken($user, &$tokenOut)
{
    if (!isset($user) || $user == false) {
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

function login_appWithToken($user, $pass, &$tokenInOut, &$scoreOut, &$messagesOut): int
{
    //error_log( __FUNCTION__ . "user:$user, tokenInOut:$tokenInOut" );

    sanitize_sql_inputs($user);

    if (!isset($user) || $user == false || mb_strlen($user) < 2) {
        // error_log(__FUNCTION__ . " username failed: empty user");
        return 0;
    }

    $passwordProvided = (isset($pass) && mb_strlen($pass) >= 1);
    $tokenProvided = (isset($tokenInOut) && mb_strlen($tokenInOut) >= 1);

    if (!$passwordProvided && !$tokenProvided) {
        return 0;
    }

    $query = null;

    if ($passwordProvided) {
        $loginUser = $user;
        $authenticated = validateUser($loginUser, $pass, $fbUser, 0);
        if (!$authenticated) {
            return 0;
        }
        $query = "SELECT RAPoints, appToken FROM UserAccounts WHERE User='$user'";
    } elseif ($tokenProvided) {
        //    Token provided:
        $query = "SELECT RAPoints, appToken, appTokenExpiry FROM UserAccounts WHERE User='$user' AND appToken='$tokenInOut'";
    }

    if (!$query) {
        return 0;
    }

    //error_log( $query );
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        if ($data !== false && mysqli_num_rows($dbResult) == 1) {
            //    Test for expired tokens!
            if ($tokenProvided) {
                $expiry = $data['appTokenExpiry'];
                if (time() > strtotime($expiry)) {
                    generateAppToken($user, $tokenInOut);
                    //    Expired!
                    // error_log(__FUNCTION__ . " failed6: user:$user, tokenInOut:$tokenInOut, $expiry, " . strtotime($expiry));
                    return -1;
                }
            }

            $scoreOut = $data['RAPoints'];
            settype($scoreOut, "integer");
            $messagesOut = GetMessageCount($user, $totalMessageCount);

            //if( $passwordProvided )
            //    generateAppToken( $user, $tokenInOut );
            //    Against my better judgement... ##SD
            if (mb_strlen($data['appToken']) != 16) {   //    Generate if new
                generateAppToken($user, $tokenInOut);
            } else {
                //    Return old token if not
                $tokenInOut = $data['appToken'];

                //    Update app token expiry now anyway

                $expDays = 14;
                $expiryStr = date("Y-m-d H:i:s", (time() + 60 * 60 * 24 * $expDays));
                $query = "UPDATE UserAccounts SET appTokenExpiry='$expiryStr' WHERE User='$user'";
                // log_sql($query);
                s_mysql_query($query);
            }

            postActivity($user, ActivityType::Login, "");

            return 1;
        } else {
            // error_log(__FUNCTION__ . " failed5: user:$user, tokenInOut:$tokenInOut");
            return 0;
        }
    }

    return 0;
}

function getUserAppToken($user)
{
    sanitize_sql_inputs($user);

    $query = "SELECT appToken FROM UserAccounts WHERE User='$user'";
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        return $data['appToken'];
    }

    return "";
}

function GetUserData($user)
{
    sanitize_sql_inputs($user);

    $query = "SELECT * FROM UserAccounts WHERE User='$user'";
    $dbResult = s_mysql_query($query);

    if ($dbResult == false || mysqli_num_rows($dbResult) != 1) {
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed: Achievement $id doesn't exist!");
        return null;
    } else {
        return mysqli_fetch_assoc($dbResult);
    }
}

function getAccountDetails(&$user, &$dataOut)
{
    if (!isset($user) || mb_strlen($user) < 2) {
        // error_log(__FUNCTION__ . " failed: user:$user");
        return false;
    }

    sanitize_sql_inputs($user);

    $query = "SELECT ID, cookie, User, EmailAddress, Permissions, RAPoints, TrueRAPoints, fbUser, fbPrefs, websitePrefs, LastActivityID, Motto, ContribCount, ContribYield, APIKey, UserWallActive, Untracked, RichPresenceMsg, LastGameID, LastLogin, Created, DeleteRequested, Deleted
                FROM UserAccounts
                WHERE User='$user'
                AND Deleted IS NULL";

    $dbResult = s_mysql_query($query);
    if ($dbResult == false || mysqli_num_rows($dbResult) !== 1) {
        // error_log(__FUNCTION__ . " failed: user:$user, query:$query");
        return false;
    } else {
        $dataOut = mysqli_fetch_array($dbResult);
        $user = $dataOut['User'];    //    Fix case!
        return true;
    }
}

function getAccountDetailsFB($fbUser, &$details)
{
    sanitize_sql_inputs($fbUser);

    $query = "SELECT User, EmailAddress, Permissions, RAPoints FROM UserAccounts WHERE fbUser='$fbUser'";
    $result = s_mysql_query($query);
    if ($result == false || mysqli_num_rows($result) !== 1) {
        // error_log(__FUNCTION__ . " failed: fbUser:$fbUser, query:$query");
        return false;
    } else {
        $details = mysqli_fetch_array($result);
        return true;
    }
}

function associateFB($user, $fbUser)
{
    sanitize_sql_inputs($user, $fbUser);

    //    TBD: Sanitise!
    $query = "UPDATE UserAccounts SET fbUser='$fbUser', Updated=NOW() WHERE User='$user'";
    //echo $query;
    // log_sql($query);
    if (s_mysql_query($query) == false) {
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed: user:$user and fbUser:$fbUser passed");
        return false;
    } else {
        // $query = "UPDATE UserAccounts SET fbPrefs=1, Updated=NOW() WHERE User='$user'";
        // log_sql( $query );
        // if( s_mysql_query( $query ) == FALSE )
        // {
        // error_log( $query );
        // error_log( __FUNCTION__ . " failed2: user:$user and fbUser:$fbUser passed" );
        // return FALSE;
        // }
    }

    //    Give them a badge :)
    AddSiteAward($user, 5, 0);

    return true;
}

function getFBUser($user, &$fbUserOut)
{
    sanitize_sql_inputs($user);

    $query = "SELECT fbUser FROM UserAccounts WHERE User='$user'";
    $dbResult = s_mysql_query($query);

    if ($dbResult == false || mysqli_num_rows($dbResult) !== 1) {
        // error_log(__FUNCTION__ . " failed: user:$user");
        return false;
    } else {
        $db_entry = mysqli_fetch_assoc($dbResult);
        $fbUserOut = $db_entry['fbUser'];
        return true;
    }
}

function getUserIDFromUser($user)
{
    sanitize_sql_inputs($user);

    $query = "SELECT ID FROM UserAccounts WHERE User LIKE '$user'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        return $data['ID'] ?? 0;
    } else {
        // error_log(__FUNCTION__ . " cannot find user $user.");
        return 0;
    }
}

function getUserFromID($userID)
{
    sanitize_sql_inputs($userID);

    $query = "SELECT User FROM UserAccounts WHERE ID ='$userID'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        return $data['User'];
    } else {
        // error_log(__FUNCTION__ . " cannot find user $user.");
        return "";
    }
}

function getUserMetadataFromID($userID)
{
    sanitize_sql_inputs($userID);

    $query = "SELECT * FROM UserAccounts WHERE ID ='$userID'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult);
    } else {
        // error_log(__FUNCTION__ . " cannot find user $user.");
        return 0;
    }
}

function getUserStats($user)
{
}

function getUserUnlockAchievement($user, $achievementID, &$dataOut)
{
    sanitize_sql_inputs($user, $achievementID);

    $query = "SELECT ach.ID, aw.HardcoreMode, aw.Date
        FROM Achievements AS ach
        LEFT JOIN Awarded AS aw ON ach.ID = aw.AchievementID
        WHERE ach.ID = '$achievementID' AND aw.User = '$user'";

    $dbResult = s_mysql_query($query);

    $dataOut = [];

    if ($dbResult === false) {
        return false;
    }

    while ($data = mysqli_fetch_assoc($dbResult)) {
        $dataOut[] = $data;
    }

    return count($dataOut);
}

function getUserUnlocksDetailed($user, $gameID, &$dataOut)
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

function GetUserUnlocksData($user, $gameID, $hardcoreMode)
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

// TODO: Deprecate
function getUserUnlocks($user, $gameID, &$dataOut, $hardcoreMode)
{
    $dataOut = GetUserUnlocksData($user, $gameID, $hardcoreMode);
    return count($dataOut);
}

function getTopUsersByScore($count, &$dataOut, $ofFriend = null)
{
    sanitize_sql_inputs($count, $ofFriend);
    settype($count, 'integer');

    if ($count > 10) {
        $count = 10;
    }

    $subquery = "WHERE !ua.Untracked";
    if (isset($ofFriend)) {
        //$subquery = "WHERE ua.User IN ( SELECT f.Friend FROM Friends AS f WHERE f.User = '$ofFriend' )
        //              OR ua.User = '$ofFriend' ";
        //    Only users whom I have added:
        $subquery = "WHERE !ua.Untracked AND ua.User IN ( SELECT f.Friend FROM Friends AS f WHERE f.User = '$ofFriend' AND f.Friendship = 1 )";
    }

    $query = "SELECT User, RAPoints, TrueRAPoints
              FROM UserAccounts AS ua
              $subquery
              ORDER BY RAPoints DESC 
              LIMIT 0, $count ";

    //echo $query;

    $dbResult = s_mysql_query($query);

    if ($dbResult == false || mysqli_num_rows($dbResult) == 0) {
        //    This is acceptible if the user doesn't have any friends!
        //error_log( __FUNCTION__ . " failed: none found: count:$count query:$query" );
        return 0;
    } else {
        $i = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            //$dataOut[$i][0] = $db_entry["ID"];
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
 *
 * @param string $user to get friend count for
 * @return int|null The number of friends for the user
 */
function getFriendCount($user)
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

function getUserForumPostAuth($user)
{
    sanitize_sql_inputs($user);

    $query = "SELECT uc.ManuallyVerified FROM UserAccounts AS uc WHERE uc.User = '$user'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        return $data['ManuallyVerified'];
    } else {
        log_sql_fail();
        // error_log(__FUNCTION__ . " issues! $userIn");
        return $user;
    }
}

function validateUsername($userIn)
{
    sanitize_sql_inputs($userIn);

    $query = "SELECT uc.User FROM UserAccounts AS uc WHERE uc.User LIKE '$userIn'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        return $data['User'];
    } else {
        log_sql_fail();
        // error_log(__FUNCTION__ . " issues! $userIn");
        return null;
    }
}

function GetScore($user)
{
    sanitize_sql_inputs($user);

    $query = "SELECT ua.RAPoints
              FROM UserAccounts AS ua
              WHERE ua.User='$user'";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $result = mysqli_fetch_assoc($dbResult);

        if (!$result) {
            return null;
        }

        $points = $result['RAPoints'];
        settype($points, 'integer');
        return $points;
    } else {
        // error_log(__FUNCTION__ . " failed: user:$user");
        return 0;
    }
}

/**
 * Gets the account age in years for the input user.
 *
 * @param string $user to get account age for
 * @return int|null The number of years the account has been created for
 */
function getAge($user)
{
    sanitize_sql_inputs($user);

    $query = "SELECT ua.Created
              FROM UserAccounts AS ua
              WHERE ua.User='$user'";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $result = mysqli_fetch_assoc($dbResult);

        if (!$result) {
            return null;
        }
        $created = strtotime($result['Created']);
        $curDate = strtotime(date('Y-m-d H:i:s'));
        $diff = $curDate - $created;

        $years = floor($diff / (365 * 60 * 60 * 24));
        return (int) $years;
    } else {
        // error_log(__FUNCTION__ . " failed: user:$user");
        return 0;
    }
}

/**
 * Gets the points or retro points rank of the user.
 *
 * @param string $user the user to get the rank for
 * @param int $type 0 for points rank, anything else for retro points rank
 * @return int rank of the user
 */
function getUserRank($user, $type = 0)
{
    sanitize_sql_inputs($user);

    // $query = "
    //     SELECT (COUNT(*) + 1) AS UserRank
    //     FROM UserAccounts
    //     WHERE NOT Untracked
    //       AND RAPoints > (SELECT RAPoints FROM UserAccounts WHERE User = '$user')
    // ";

    if ($type == 0) {
        $joinCond = "RIGHT JOIN UserAccounts AS ua2 ON ua.RAPoints < ua2.RAPoints AND NOT ua2.Untracked";
    } else {
        $joinCond = "RIGHT JOIN UserAccounts AS ua2 ON ua.TrueRAPoints < ua2.TrueRAPoints AND NOT ua2.Untracked";
    }

    $query = "SELECT ( COUNT(*) + 1 ) AS UserRank
                FROM UserAccounts AS ua
                $joinCond
                WHERE ua.User = '$user'";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        return (int) $data['UserRank'];
    }

    log_sql_fail();
    // error_log(__FUNCTION__ . " could not find $user in db?!");

    return 0;
}

function countRankedUsers()
{
    $query = "
        SELECT COUNT(*) AS count
        FROM UserAccounts
        WHERE RAPoints >= " . MIN_POINTS . "
          AND NOT Untracked
    ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult)['count'];
    } else {
        return false;
    }
}

function updateAchievementVote($achID, $posDiff, $negDiff)
{
    sanitize_sql_inputs($achID, $posDiff, $negDiff);

    //    Tell achievement $achID that it's vote count has been changed by $posDiff and $negDiff

    $query = "UPDATE Achievements SET VotesPos=VotesPos+$posDiff, VotesNeg=VotesNeg+$negDiff, Updated=NOW() WHERE ID=$achID";
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        return true;
    } else {
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed(;): achID:$achID, posDiff:$posDiff, negDiff:$negDiff");
        return false;
    }
}

function applyVote($user, $achID, $vote)
{
    sanitize_sql_inputs($user, $achID, $vote);
    settype($vote, 'integer');
    if ($vote != 1 && $vote != -1) {
        // error_log(__FUNCTION__ . " failed: illegal vote:$vote by user:$user, achID:$achID");
        return false;
    }

    $posVote = ($vote == 1);
    $negVote = ($vote == -1);
    settype($posVote, 'integer');
    settype($negVote, 'integer');

    $query = "SELECT * FROM Votes WHERE User='$user' AND AchievementID='$achID'";
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false && mysqli_num_rows($dbResult) == 0) {
        //    Vote not yet cast - add it newly!
        $query = "INSERT INTO Votes (User, AchievementID, Vote) VALUES ( '$user', '$achID', $vote )";
        // log_sql($query);
        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            return updateAchievementVote($achID, $posVote, $negVote);
        } else {
            log_sql_fail();
            // error_log(__FUNCTION__ . " failed(;): user:$user, achID:$achID, vote:$vote");
            return false;
        }
    } else {
        //    Vote already cast: update it to the selected one.
        $data = mysqli_fetch_assoc($dbResult);
        $voteAlreadyCast = $data['Vote'];
        if ($voteAlreadyCast == $vote) {
            //    Vote already posted... ignore?
            // error_log(__FUNCTION__ . " warning: Identical vote already cast. ($user, $achID)");
            return true;
        } else {
            $query = "UPDATE Votes SET Vote=$vote WHERE User='$user' AND AchievementID='$achID'";

            $dbResult = s_mysql_query($query);
            if ($dbResult !== false) {
                //    Updated Votes. Now update ach:
                if ($vote == 1 && $voteAlreadyCast == -1) {
                    //    Changing my vote to pos
                    return updateAchievementVote($achID, 1, -1);
                } else {
                    //    Changing my vote to neg
                    return updateAchievementVote($achID, -1, 1);
                }
            } else {
                log_sql_fail();
                // error_log(__FUNCTION__ . " failed(;): user:$user, achID:$achID, vote:$vote");
                return false;
            }
        }
    }
}

function getUserActivityRange($user, &$firstLogin, &$lastLogin)
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

function getUserProgress($user, $gameIDsCSV, &$dataOut)
{
    if (empty($gameIDsCSV) || !isValidUsername($user)) {
        return null;
    }
    sanitize_sql_inputs($user);

    //    Create null entries so that we pass 'something' back.
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

    //    Count num possible achievements
    $query = "SELECT GameID, COUNT(*) AS AchCount, SUM(ach.Points) AS PointCount FROM Achievements AS ach
              WHERE ach.Flags = 3 AND ach.GameID IN ( $gameIDs )
              GROUP BY ach.GameID
              HAVING COUNT(*)>0 ";

    //error_log( $query );

    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        // error_log(__FUNCTION__ . "buggered up somehow. $user, $gameIDsCSV");
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

    //    Foreach return value from this, cross reference with 'earned' achievements. If not found, assume 0.
    //    Count earned achievements
    $query = "SELECT GameID, COUNT(*) AS AchCount, SUM( ach.Points ) AS PointCount, aw.HardcoreMode
              FROM Awarded AS aw
              LEFT JOIN Achievements AS ach ON aw.AchievementID = ach.ID
              WHERE ach.GameID IN ( $gameIDsCSV ) AND ach.Flags = 3 AND aw.User = '$user'
              GROUP BY aw.HardcoreMode, ach.GameID";

    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        // error_log(__FUNCTION__ . "buggered up in the second part. $user, $gameIDsCSV");
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

function GetAllUserProgress($user, $consoleID)
{
    $retVal = [];
    sanitize_sql_inputs($user, $consoleID);
    settype($consoleID, 'integer');

    //Title,
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
            //    Auto:
            //$retVal[] = $nextData;
            //    Manual:
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

function getUsersGameList($user, &$dataOut)
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
    if ($dbResult == false) {
        //log_email(__FUNCTION__ . " failed with $user");

        // error_log(__FUNCTION__ . "1 $user ");
        return 0;
    }

    $gamelistCSV = '0';

    while ($nextData = mysqli_fetch_assoc($dbResult)) {
        $dataOut[$nextData['ID']] = $nextData;
        $gamelistCSV .= ', ' . $nextData['ID'];
    }

    //    Get totals:
    $query = "SELECT ach.GameID, gd.Title, COUNT(ach.ID) AS NumAchievements
            FROM Achievements AS ach
            LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
            WHERE ach.Flags = 3 AND ach.GameID IN ( $gamelistCSV )
            GROUP BY ach.GameID ";

    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        //log_email($query);
        // error_log(__FUNCTION__ . "2 $user ");
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

function getUsersRecentAwardedForGames($user, $gameIDsCSV, $numAchievements, &$dataOut)
{
    sanitize_sql_inputs($user, $numAchievements);
    settype($numAchievements, 'integer');

    $gameIDsArray = explode(',', $gameIDsCSV);

    $numIDs = count($gameIDsArray);
    if ($numIDs == 0) {
        return;
    }

    $gameIDs = [];
    foreach ($gameIDsArray as $gameID) {
        settype($gameID, "integer");
        $gameIDs[] = $gameID;
    }
    $gameIDs = implode(',', $gameIDs);

    $limit = ($numAchievements == 0) ? 5000 : $numAchievements;
    //echo $numIDs;
    //error_log( $gameIDsCSV );

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
    } else {
        // error_log(__FUNCTION__ . " something's gone wrong :( $user, $gameIDsCSV, $numAchievements");
    }
}

function getUserPageInfo(&$user, &$libraryOut, $numGames, $numRecentAchievements, $localUser)
{
    sanitize_sql_inputs($user, $localUser);

    getAccountDetails($user, $userInfo);

    if (!$userInfo) {
        return null;
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

    $libraryOut['Rank'] = getUserRank($user); //    ANOTHER call... can't we cache this?

    $numRecentlyPlayed = count($recentlyPlayedData);

    if ($numRecentlyPlayed > 0) {
        $gameIDsCSV = $recentlyPlayedData[0]['GameID'];

        for ($i = 1; $i < $numRecentlyPlayed; $i++) {
            $gameIDsCSV .= ", " . $recentlyPlayedData[$i]['GameID'];
        }

        //echo $gameIDsCSV;

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

        //echo $query;

        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            while ($db_entry = mysqli_fetch_assoc($dbResult)) {
                if ($db_entry['Local'] == 1) {
                    $libraryOut['Friendship'] = $db_entry['Friendship'];
                } else { //if( $db_entry['Local'] == 0 )
                    $libraryOut['FriendReciprocation'] = $db_entry['Friendship'];
                }
            }
        } else {
            log_sql_fail();
        }
    }
}

function getControlPanelUserInfo($user, &$libraryOut)
{
    sanitize_sql_inputs($user);

    $libraryOut = [];
    $libraryOut['Played'] = [];
    //getUserActivityRange( $user, $firstLogin, $lastLogin );
    //$libraryOut['MemberSince'] = $firstLogin;
    //$libraryOut['LastLogin'] = $lastLogin;

    $query = "SELECT gd.ID, c.Name AS ConsoleName, gd.Title AS GameTitle, COUNT(*) AS NumAwarded, Inner1.NumPossible
                FROM Awarded AS aw
                LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
                LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
                LEFT JOIN (
                    SELECT ach.GameID, COUNT(*) AS NumPossible
                    FROM Achievements AS ach
                    GROUP BY ach.GameID ) AS Inner1 ON Inner1.GameID = gd.ID
                WHERE aw.User = '$user' AND aw.HardcoreMode = 0
                GROUP BY gd.ID, gd.ConsoleID, gd.Title
                ORDER BY gd.Title, gd.ConsoleID";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $libraryOut['Played'][] = $db_entry;
        }   //    use as raw array to preserve order!

        return true;
    } else {
        // error_log(__FUNCTION__);
        log_sql_fail();

        return false;
    }
}

function getUserList($sortBy, $offset, $count, &$dataOut, $requestedBy)
{
    return getUserListByPerms($sortBy, $offset, $count, $dataOut, $requestedBy, $permissions, false);
}

function getUserListByPerms($sortBy, $offset, $count, &$dataOut, $requestedBy, &$perms = null, $showUntracked = false)
{
    sanitize_sql_inputs($offset, $count, $requestedBy, $perms);
    settype($offset, 'integer');
    settype($count, 'integer');
    settype($showUntracked, 'boolean');

    $whereQuery = null;
    $permsFilter = null;

    settype($perms, 'integer');
    if ($perms >= Permissions::Spam && $perms <= Permissions::Unregistered || $perms == Permissions::SuperUser) {
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
    switch ($sortBy) {
        case 1: // Default sort:
            $orderBy = "ua.User ASC ";
            break;
        case 11:
            $orderBy = "ua.User DESC ";
            break;

        case 2: // RAPoints
            $orderBy = "ua.RAPoints DESC ";
            break;
        case 12:
            $orderBy = "ua.RAPoints ASC ";
            break;

        case 3: // NumAwarded
            $orderBy = "NumAwarded DESC ";
            break;
        case 13:
            $orderBy = "NumAwarded ASC ";
            break;

        case 4: // LastLogin
            $orderBy = "ua.LastLogin DESC ";
            break;
        case 14:
            $orderBy = "ua.LastLogin ASC ";
            break;

        default:
            $orderBy = "ua.User ASC ";
    }

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
        // error_log(__FUNCTION__);
        log_sql_fail();
    }

    return $numFound;
}

/**
 * @return int|mixed|string
 */
function getUserPermissions(?string $user)
{
    if ($user == null) {
        return 0;
    }

    sanitize_sql_inputs($user);

    $query = "SELECT Permissions FROM UserAccounts WHERE User='$user'";
    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        // error_log(__FUNCTION__);
        log_sql_fail();
        return 0;
    } else {
        $data = mysqli_fetch_assoc($dbResult);
        return $data['Permissions'];
    }
}

function getUsersCompletedGamesAndMax($user)
{
    $retVal = [];

    if (!isValidUsername($user)) {
        return $retVal;
    }

    sanitize_sql_inputs($user);

    $requiredFlags = 3;
    $minAchievementsForCompletion = 5;

    $query = "SELECT gd.ID AS GameID, c.Name AS ConsoleName, gd.ImageIcon, gd.Title, COUNT(ach.GameID) AS NumAwarded, inner1.MaxPossible, (COUNT(ach.GameID)/inner1.MaxPossible) AS PctWon, aw.HardcoreMode
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
    } else {
        //log_email($query);
        //log_email("failing");
    }

    return $retVal;
}

function getUsersSiteAwards($user, $showHidden = false)
{
    sanitize_sql_inputs($user);

    $retVal = [];

    if (!isValidUsername($user)) {
        return $retVal;
    }

    $hiddenQuery = "";
    if ($showHidden == false) {
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

        //Updated way to "squash" duplicate awards to work with the new site award ordering implementation
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

        //Get a single list of games both completed and mastered
        if (count($completedGames) > 0 && count($masteredGames) > 0) {
            $multiAwardGames = array_intersect($completedGames, $masteredGames);

            //For games that have been both completed and mastered, remove the completed entry from the award array.
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

        //Remove blank indexes
        $retVal = array_values(array_filter($retVal));
    } else {
        //log_email($query);
        //log_email("failing");
    }

    return $retVal;
}

function AddSiteAward($user, $awardType, $data, $dataExtra = 0)
{
    sanitize_sql_inputs($user, $awardType, $data, $dataExtra);
    settype($awardType, 'integer');
    //settype( $data, 'integer' );    //    nullable
    settype($dataExtra, 'integer');

    $displayOrder = 0;
    $query = "SELECT MAX( DisplayOrder ) FROM SiteAwards WHERE User = '$user'";
    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        // error_log(__FUNCTION__);
        log_sql_fail();
    } else {
        $dbData = mysqli_fetch_assoc($dbResult);
        if (isset($dbData['MAX( DisplayOrder )'])) {
            $displayOrder = (int) $dbData['MAX( DisplayOrder )'] + 1;
        }
    }

    $query = "INSERT INTO SiteAwards (AwardDate, User, AwardType, AwardData, AwardDataExtra, DisplayOrder) 
                            VALUES( NOW(), '$user', '$awardType', '$data', '$dataExtra', '$displayOrder' ) ON DUPLICATE KEY UPDATE AwardDate = NOW()";
    // log_sql($query);
    global $db;
    $dbResult = mysqli_query($db, $query);
    if ($dbResult != null) {
        //error_log( "AddSiteAward OK! $user, $awardType, $data" );
        //log_email( __FUNCTION__ . " $user, $awardType, $data" );
    } else {
        //log_email("Failed AddSiteAward: $query");
    }
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
    SQL_ASSERT($dbResult);

    $retVal = [];
    while ($db_entry = mysqli_fetch_assoc($dbResult)) {
        settype($db_entry['NumCreated'], 'integer');
        $retVal[] = $db_entry;
    }
    return $retVal;
}

function GetDeveloperStatsFull($count, $sortBy)
{
    sanitize_sql_inputs($count);
    settype($sortBy, 'integer');
    settype($count, 'integer');

    switch ($sortBy) {
        case 1: // number of points allocated
            $order = "ContribYield DESC";
            break;
        case 2: // number of achievements won by others
            $order = "ContribCount DESC";
            break;
        case 3:
            $order = "OpenTickets DESC";
            break;
        case 4:
            $order = "TicketRatio DESC";
            break;
        case 5:
            $order = "LastLogin DESC";
            break;
        case 6:
            $order = "Author ASC";
            break;
        case 0:
        default:
            $order = "Achievements DESC";
    }

    $query = "
    SELECT
        ua.User AS Author,
        Permissions,
        ContribCount,
        ContribYield,
        COUNT(DISTINCT(ach.ID)) AS Achievements,
        COUNT(tick.ID) AS OpenTickets,
        COUNT(tick.ID)/COUNT(ach.ID) AS TicketRatio,
        LastLogin
    FROM
        UserAccounts AS ua
    LEFT JOIN
        Achievements AS ach ON (ach.Author = ua.User AND ach.Flags = 3)
    LEFT JOIN
        Ticket AS tick ON (tick.AchievementID = ach.ID AND tick.ReportState = 1)
    WHERE
        ContribCount > 0 AND ContribYield > 0
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
    } else {
        // error_log(__FUNCTION__ . " failed?! $count");
    }

    return $retVal;
}

function GetUserFields($username, $fields)
{
    sanitize_sql_inputs($username);

    $fieldsCSV = implode(",", $fields);
    $query = "SELECT $fieldsCSV FROM UserAccounts AS ua
              WHERE ua.User = '$username'";
    //error_log( $query );
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

function SetUserTrackedStatus($usernameIn, $isUntracked)
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
    $userCardInfo['Motto'] = htmlspecialchars($userInfo['Motto']);
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
    if ($dbResult == false) {
        // global $db;
        // var_dump(mysqli_error($db));
        // error_log(__FUNCTION__ . " failed for $user!");
        return false;
    } else {
        //error_log( __FUNCTION__ );
        //error_log( "recalc'd $user's score as " . getScore($user) . ", OK!" );
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

    //    Update the fact that this author made an achievement that just got earned.
    $query = "UPDATE UserAccounts AS ua
              SET ua.ContribCount = ua.ContribCount+1, ua.ContribYield = ua.ContribYield + $points
              WHERE ua.User = '$author'";

    $dbResult = s_mysql_query($query);

    if ($dbResult == false) {
        log_sql_fail();
    } else {
        //error_log( __FUNCTION__ . " $author, $points" );

        for ($i = 0; $i < count(RA\AwardThreshold::DEVELOPER_COUNT_BOUNDARIES); $i++) {
            if ($oldContribCount < RA\AwardThreshold::DEVELOPER_COUNT_BOUNDARIES[$i] && $oldContribCount + 1 >= RA\AwardThreshold::DEVELOPER_COUNT_BOUNDARIES[$i]) {
                //This developer has arrived at this point boundary!
                AddSiteAward($author, 2, $i);
            }
        }
        for ($i = 0; $i < count(RA\AwardThreshold::DEVELOPER_POINT_BOUNDARIES); $i++) {
            if ($oldContribYield < RA\AwardThreshold::DEVELOPER_POINT_BOUNDARIES[$i] && $oldContribYield + $points >= RA\AwardThreshold::DEVELOPER_POINT_BOUNDARIES[$i]) {
                //This developer is newly above this point boundary!
                AddSiteAward($author, 3, $i);
            }
        }
    }
}

function recalculateDevelopmentContributions($user)
{
    sanitize_sql_inputs($user);

    //##SD Should be rewritten using a single inner table... damnit!

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

    return $dbResult != false;
}

/*
 * Gets completed and mastered counts for all users who have played the passed in games.
 *
 * @param Array $gameIDs game ID to check awards for
 * @return Array|NULL of user completed and mastered data
 */
function getMostAwardedUsers($gameIDs)
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

/*
 * Gets completed and mastered counts for all the passed in games.
 *
 * @param Array $gameIDs game ID to check awards for
 * @return Array|NULL of game data
 */
function getMostAwardedGames($gameIDs)
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

function cancelDeleteRequest($username): bool
{
    getAccountDetails($username, $user);

    $query = "UPDATE UserAccounts u SET u.DeleteRequested = NULL WHERE u.User = '$username'";
    $dbResult = s_mysql_query($query);
    return $dbResult !== false;
}

function deleteRequest($username, $date = null): bool
{
    getAccountDetails($username, $user);

    if ($user['DeleteRequested']) {
        return false;
    }

    // Cap permissions to 1
    $permission = min($user['Permissions'], 1);

    $date = $date ?? date('Y-m-d H:i:s');
    $query = "UPDATE UserAccounts u SET u.DeleteRequested = '$date', u.Permissions = $permission WHERE u.User = '$username'";
    $dbResult = s_mysql_query($query);
    return $dbResult !== false;
}

function deleteOverdueUserAccounts()
{
    $threshold = date('Y-m-d 08:00:00', time() - 60 * 60 * 24 * 14);

    $query = "SELECT * FROM UserAccounts u WHERE u.DeleteRequested <= '$threshold' AND u.Deleted IS NULL ORDER BY u.DeleteRequested";

    $dbResult = s_mysql_query($query);
    if ($dbResult === false) {
        return;
    }

    foreach ($dbResult as $user) {
        clearAccountData($user);
    }
}

function clearAccountData($user)
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
    if ($dbResult == false) {
        echo mysqli_error($db);
    }
    $dbResult = s_mysql_query("DELETE FROM Awarded WHERE User = '$username'");
    if ($dbResult == false) {
        echo mysqli_error($db) . PHP_EOL;
    }
    $dbResult = s_mysql_query("DELETE FROM EmailConfirmations WHERE User = '$username'");
    if ($dbResult == false) {
        echo mysqli_error($db) . PHP_EOL;
    }
    $dbResult = s_mysql_query("DELETE FROM Friends WHERE User = '$username' OR Friend = '$username'");
    if ($dbResult == false) {
        echo mysqli_error($db) . PHP_EOL;
    }
    $dbResult = s_mysql_query("DELETE FROM Rating WHERE User = '$username'");
    if ($dbResult == false) {
        echo mysqli_error($db) . PHP_EOL;
    }
    $dbResult = s_mysql_query("DELETE FROM SetRequest WHERE User = '$username'");
    if ($dbResult == false) {
        echo mysqli_error($db) . PHP_EOL;
    }
    $dbResult = s_mysql_query("DELETE FROM SiteAwards WHERE User = '$username'");
    if ($dbResult == false) {
        echo mysqli_error($db) . PHP_EOL;
    }
    $dbResult = s_mysql_query("DELETE FROM Subscription WHERE UserID = '$userId'");
    if ($dbResult == false) {
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
        WHERE ID = '$userId'");
    if ($dbResult == false) {
        echo mysqli_error($db) . PHP_EOL;
    }

    removeAvatar($username);

    echo "SUCCESS" . PHP_EOL;
}

/**
 * APIKey doesn't have to be reset -> permission >= Registered
 * @param mixed $permissions
 */
function banAccountByUsername(string $username, $permissions)
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
        WHERE u.User='$username'");
    if ($dbResult == false) {
        echo mysqli_error($db) . PHP_EOL;
    }

    removeAvatar($username);

    echo "SUCCESS" . PHP_EOL;
}
