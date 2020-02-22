<?php

use RA\ActivityType;
use RA\Permissions;

abstract class UserPref
{
    const EmailOn_ActivityComment = 0;

    const EmailOn_AchievementComment = 1;

    const EmailOn_UserWallComment = 2;

    const EmailOn_ForumReply = 3;

    const EmailOn_AddFriend = 4;

    const EmailOn_PrivateMessage = 5;

    const EmailOn_Newsletter = 6;

    const EmailOn_unused2 = 7;

    const SiteMsgOn_ActivityComment = 8;

    const SiteMsgOn_AchievementComment = 9;

    const SiteMsgOn_UserWallComment = 10;

    const SiteMsgOn_ForumReply = 11;

    const SiteMsgOn_AddFriend = 12;
}

abstract class FBUserPref
{
    const PostFBOn_EarnAchievement = 0;

    const PostFBOn_CompleteGame = 1;

    const PostFBOn_UploadAchievement = 2;
}

//////////////////////////////////////////////////////////////////////////////////////////
//    Accounts
//////////////////////////////////////////////////////////////////////////////////////////

function generateEmailValidationString($user)
{
    $emailCookie = rand_string(16);
    $expiry = time() + 60 * 60 * 24 * 7;

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

function SetAccountPermissionsJSON($sourceUser, $sourcePermissions, $destUser, $newPermissions)
{
    $retVal = [];
    $destPermissions = getUserPermissions($destUser);

    $retVal['Success'] = true;
    $retVal['DestUser'] = $destUser;
    $retVal['DestPrevPermissions'] = $destPermissions;
    $retVal['NewPermissions'] = $newPermissions;

    if ($destPermissions > $sourcePermissions) {
        //    Ignore: this person cannot be demoted by a lower-level member
        // error_log(__FUNCTION__ . " failed: $sourceUser ($sourcePermissions) is trying to set $destUser ($destPermissions) to $newPermissions??! Not allowed!");
        $retVal['Error'] = "$sourceUser ($sourcePermissions) is trying to set $destUser ($destPermissions) to $newPermissions??! Not allowed!";
        $retVal['Success'] = false;
    } elseif (($newPermissions >= Permissions::Admin) && ($sourcePermissions != Permissions::Root)) {
        //    Ignore: cannot promote to admin unless you are root
        // error_log(__FUNCTION__ . " failed: $sourceUser ($sourcePermissions) is trying to set $destUser ($destPermissions) to $newPermissions??! Changing to admin requires Root account ('Scott')!");
        $retVal['Error'] = "$sourceUser ($sourcePermissions) is trying to set $destUser ($destPermissions) to $newPermissions??! Changing to admin requires Root account ('Scott')!";
        $retVal['Success'] = false;
    } else {
        $query = "UPDATE UserAccounts SET Permissions = $newPermissions, Updated=NOW() WHERE User='$destUser'";
        // log_sql($query);
        $dbResult = s_mysql_query($query);
        if ($dbResult == false) {
            //    Unrecognised user?
            // error_log(__FUNCTION__ . " failed: $sourceUser ($sourcePermissions) is trying to set $destUser ($destPermissions) to $newPermissions??! Cannot find user: '$destUser'!");
            $retVal['Error'] = "$sourceUser ($sourcePermissions) is trying to set $destUser ($destPermissions) to $newPermissions??! Cannot find user: '$destUser'!";
            $retVal['Success'] = false;
        } else {
            // error_log(__FUNCTION__ . " success: $sourceUser ($sourcePermissions) changed $destUser ($destPermissions) to $newPermissions.");
        }
    }

    return $retVal;
}

function setAccountPermissions($sourceUser, $sourcePermissions, $user, $permissions)
{
    $existingPermissions = getUserPermissions($user);
    if ($existingPermissions > $sourcePermissions) {
        //    Ignore: this person cannot be demoted by a lower-level member
        // error_log(__FUNCTION__ . " failed: $sourceUser ($sourcePermissions) is trying to set $user ($existingPermissions) to $permissions??! not allowed!");
        return false;
    } elseif (($permissions >= Permissions::Admin) && ($sourceUser != 'Scott')) {
        // error_log(__FUNCTION__ . " failed: person who is not Scott trying to set a user's permissions to admin");
        return false;
    } else {
        $query = "UPDATE UserAccounts SET Permissions = $permissions, Updated=NOW() WHERE User='$user'";
        // log_sql($query);
        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            return true;
        } else {
            //    Unrecognised  user
            // error_log(__FUNCTION__ . " failed: cannot update $user in UserAccounts??! $user, $permissions");
            return false;
        }
    }
}

function setAccountForumPostAuth($sourceUser, $sourcePermissions, $user, $permissions)
{
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
    $query = "SELECT * FROM EmailConfirmations WHERE EmailCookie='$emailCookie'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        if (mysqli_num_rows($dbResult) == 1) {
            $data = mysqli_fetch_assoc($dbResult);
            $user = $data['User'];
            $query = "DELETE FROM EmailConfirmations WHERE User='$user' AND EmailCookie='$emailCookie'";
            // log_sql($query);
            $dbResult = s_mysql_query($query);
            if ($dbResult !== false) {
                $response = SetAccountPermissionsJSON('Scott', Permissions::Admin, $user, 1);
                //if( setAccountPermissions( 'Scott', \RA\Permissions::Admin, $user, 1 ) )
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
    $newToken = rand_string(16);

    $expDays = 30;
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

function login_appWithToken($user, $pass, &$tokenInOut, &$scoreOut, &$messagesOut)
{
    //error_log( __FUNCTION__ . "user:$user, tokenInOut:$tokenInOut" );

    if (!isset($user) || $user == false || mb_strlen($user) < 2) {
        // error_log(__FUNCTION__ . " username failed: empty user");
        return 0;
    }

    $passwordProvided = (isset($pass) && mb_strlen($pass) >= 1);
    $tokenProvided = (isset($tokenInOut) && mb_strlen($tokenInOut) >= 1);

    if ($passwordProvided) {
        //error_log( $query );
        $saltedPass = md5($pass . getenv('RA_PASSWORD_SALT'));
        $query = "SELECT RAPoints, appToken FROM UserAccounts WHERE User='$user' AND SaltedPass='$saltedPass'";
    } elseif ($tokenProvided) {
        //    Token provided:
        $query = "SELECT RAPoints, appToken, appTokenExpiry FROM UserAccounts WHERE User='$user' AND appToken='$tokenInOut'";
    } else {
        // error_log(__FUNCTION__ . " token and pass failed: user:$user");
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
                generateAppToken($user, $tokenInOut);  //
            } else {
                //    Return old token if not
                $tokenInOut = $data['appToken'];

                //    Update app token expiry now anyway

                $expDays = 30;
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
    } else {
        // error_log(__FUNCTION__ . " failed4: user:$user, tokenInOut:$tokenInOut");
        return 0;
    }
}

function getUserAppToken($user)
{
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

    $query = "SELECT ID, cookie, User, EmailAddress, Permissions, RAPoints, TrueRAPoints, fbUser, fbPrefs, websitePrefs, LastActivityID, Motto, ContribCount, ContribYield, APIKey, UserWallActive, Untracked, RichPresenceMsg, LastGameID, LastLogin, Created
                FROM UserAccounts
                WHERE User='$user'";

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
    $query = "SELECT User, EmailAddress, Permissions, RAPoints FROM UserAccounts WHERE fbUser='$fbUser'";
    $result = s_mysql_query($query);
    if ($result == false || mysqli_num_rows($dbResult) !== 1) {
        // error_log(__FUNCTION__ . " failed: fbUser:$fbUser, query:$query");
        return false;
    } else {
        $details = mysqli_fetch_array($result);
        return true;
    }
}

function changePassword($user, $pass)
{
    //    Add salt
    $saltedHash = md5($pass . getenv('RA_PASSWORD_SALT'));

    if (mb_strrchr(' ', $saltedHash) || mb_strlen($saltedHash) != 32) {
        // error_log(__FUNCTION__ . " failed: new pass $pass contains invalid characters or is not 32 chars long!");
        return false;
    }

    $query = "UPDATE UserAccounts SET SaltedPass='$saltedHash', Updated=NOW() WHERE user='$user'";
    // log_sql($query);
    if (s_mysql_query($query) == true) {
        return true;
    } else {
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed: user:$user");
        return false;
    }
}

function associateFB($user, $fbUser)
{
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

function getUserForumPostAuth($user)
{
    $query = "SELECT uc.ManuallyVerified FROM UserAccounts AS uc WHERE uc.User = '$user'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        return $data['ManuallyVerified'];
    } else {
        log_sql_fail();
        // error_log(__FUNCTION__ . " issues! $userIn");
        return $userIn;
    }
}

function correctUserCase($userIn)
{
    $query = "SELECT uc.User FROM UserAccounts AS uc WHERE uc.User LIKE '$userIn'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        return $data['User'];
    } else {
        log_sql_fail();
        // error_log(__FUNCTION__ . " issues! $userIn");
        return $userIn;
    }
}

function GetScore($user)
{
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

function getUserRank($user)
{
    $query = "SELECT ( COUNT(*) + 1 ) AS UserRank
                FROM UserAccounts AS ua
                RIGHT JOIN UserAccounts AS ua2 ON ua.RAPoints < ua2.RAPoints
                WHERE ua.User = '$user'";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        return $data['UserRank'];
    }

    log_sql_fail();
    // error_log(__FUNCTION__ . " could not find $user in db?!");

    return 0;
}

function countRankedUsers()
{
    $query = "
        SELECT COUNT(*) AS count
        FROM UserAccounts as ua
        WHERE ua.RAPoints > 0 ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult)['count'];
    } else {
        return false;
    }
}

function updateAchievementVote($achID, $posDiff, $negDiff)
{
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
    $query = "SELECT MIN(act.timestamp) AS FirstLogin, MAX(act.timestamp) AS LastLogin
              FROM Activity AS act
              WHERE act.User = '$user' AND act.activitytype=2";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        $firstLogin = $data['FirstLogin'];
        $lastLogin = $data['LastLogin'];

        return $firstLogin !== null || $lastLogin !== null;
    }

    return false;
}

function getUserProgress($user, $gameIDsCSV, &$dataOut)
{
    //    Create null entries so that we pass 'something' back.
    $gameIDsArray = explode(',', $gameIDsCSV);
    foreach ($gameIDsArray as $gameID) {
        settype($gameID, "integer");
        $dataOut[$gameID]['NumPossibleAchievements'] = 0;
        $dataOut[$gameID]['PossibleScore'] = 0;
        $dataOut[$gameID]['NumAchieved'] = 0;
        $dataOut[$gameID]['ScoreAchieved'] = 0;
        $dataOut[$gameID]['NumAchievedHardcore'] = 0;
        $dataOut[$gameID]['ScoreAchievedHardcore'] = 0;
    }

    //    Count num possible achievements
    $query = "SELECT GameID, COUNT(*) AS AchCount, SUM(ach.Points) AS PointCount FROM Achievements AS ach
              WHERE ach.Flags = 3 AND ach.GameID IN ( $gameIDsCSV )
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
    $gameIDsArray = explode(',', $gameIDsCSV);

    $numIDs = count($gameIDsArray);
    if ($numIDs == 0) {
        return;
    }

    //echo $numIDs;
    //error_log( $gameIDsCSV );

    $query = "SELECT ach.ID, ach.GameID, gd.Title AS GameTitle, ach.Title, ach.Description, ach.Points, ach.BadgeName, (!ISNULL(aw.User)) AS IsAwarded, aw.Date AS DateAwarded, (aw.HardcoreMode) AS HardcoreAchieved
              FROM Achievements AS ach
              LEFT OUTER JOIN Awarded AS aw ON aw.User = '$user' AND aw.AchievementID = ach.ID
              LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
              WHERE ach.Flags = 3 AND ach.GameID IN ( $gameIDsCSV )
              ORDER BY IsAwarded DESC, HardcoreAchieved ASC, DateAwarded DESC, ach.DisplayOrder ASC, ach.ID ASC
              LIMIT 5000";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$db_entry['GameID']][$db_entry['ID']] = $db_entry;
        }
    } else {
        // error_log(__FUNCTION__ . " something's gone wrong :( $user, $gameIDsCSV, $numAchievements");
    }
}

function getUserPageInfo($user, &$libraryOut, $numGames, $numRecentAchievements, $localUser)
{
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
    $libraryOut['RichPresenceMsg'] = empty($userInfo['RichPresenceMsg']) || $userInfo['RichPresenceMsg'] === 'Unknown' ? null : strip_tags($userInfo['RichPresenceMsg']);
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
    $libraryOut['Motto'] = htmlspecialchars($userInfo['Motto']);

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
                GROUP BY gd.ID
                ORDER BY ConsoleID, gd.Title";

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
    return getUserListByPerms($sortBy, $offset, $count, $dataOut, $requestedBy, null, false);
}

function getUserListByPerms($sortBy, $offset, $count, &$dataOut, $requestedBy, &$perms = null, $showUntracked = false)
{
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

    $query = "    SELECT ua.ID, ua.User, ua.RAPoints, ua.TrueRAPoints, ua.LastLogin,
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
 * @param $user
 * @return int|mixed|string
 */
function getUserPermissions($user)
{
    if ($user == null) {
        return 0;
    }

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
        GROUP BY ach.GameID, aw.HardcoreMode
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

function getUsersSiteAwards($user)
{
    $retVal = [];

    if (!isValidUsername($user)) {
        return $retVal;
    }

    $query = "
    (
    SELECT UNIX_TIMESTAMP( saw.AwardDate ) as AwardedAt, saw.AwardType, saw.AwardData, saw.AwardDataExtra, saw.DisplayOrder, gd.Title, c.Name AS ConsoleName, gd.Flags, gd.ImageIcon
                  FROM SiteAwards AS saw
                  LEFT JOIN GameData AS gd ON ( gd.ID = saw.AwardData AND saw.AwardType = 1 )
                  LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
                  WHERE saw.AwardType = 1 AND saw.User = '$user'
                  GROUP BY saw.AwardType, saw.AwardData, saw.AwardDataExtra
    )
    UNION
    (
    SELECT UNIX_TIMESTAMP( saw.AwardDate ) as AwardedAt, saw.AwardType, MAX( saw.AwardData ), saw.AwardDataExtra, saw.DisplayOrder, NULL, NULL, NULL, NULL
                  FROM SiteAwards AS saw
                  WHERE saw.AwardType > 1 AND saw.User = '$user'
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
            $displayOrder = $dbData['MAX( DisplayOrder )'] + 1;
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
        COUNT(ach.ID) AS Achievements,
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
    $fieldsCSV = implode(",", $fields);
    $query = "SELECT $fieldsCSV FROM UserAccounts AS ua
              WHERE ua.User = '$username'";
    //error_log( $query );
    $dbResult = s_mysql_query($query);
    return mysqli_fetch_assoc($dbResult);
}

/**
 * @param $usernameIn
 * @return bool
 */
function HasPatreonBadge($usernameIn)
{
    $query = "SELECT * FROM SiteAwards AS sa "
        . "WHERE sa.AwardType = 6 AND sa.User = '$usernameIn'";

    $dbResult = s_mysql_query($query);
    return mysqli_num_rows($dbResult) > 0;
}

function SetPatreonSupporter($usernameIn, $enable)
{
    if ($enable) {
        AddSiteAward($usernameIn, 6, 0, 0);
    } else {
        $query = "DELETE FROM SiteAwards WHERE User = '$usernameIn' AND AwardType = '6'";
        s_mysql_query($query);
    }
}

function SetUserTrackedStatus($usernameIn, $isUntracked)
{
    $query = "UPDATE UserAccounts SET Untracked = $isUntracked, Updated=NOW() WHERE User = \"$usernameIn\"";
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
    $userCardInfo['LastActivity'] = $userInfo['LastLogin'];
    $userCardInfo['MemberSince'] = $userInfo['Created'];
}

function recalcScore($user)
{
    $query = "UPDATE UserAccounts SET RAPoints = (
                SELECT SUM(ach.Points) FROM Awarded AS aw
                LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                WHERE aw.User = '$user'
                ),
                TrueRAPoints = (
                SELECT SUM(ach.TrueRatio) FROM Awarded AS aw
                LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                WHERE aw.User = '$user'
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
    $query = "SELECT ContribCount, ContribYield FROM UserAccounts WHERE User = '$author'";
    $dbResult = s_mysql_query($query);
    $oldResults = mysqli_fetch_assoc($dbResult);
    $oldContribCount = $oldResults['ContribCount'];
    $oldContribYield = $oldResults['ContribYield'];

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
    $query = "SELECT User,
              SUM(CASE WHEN AwardDataExtra LIKE '0' THEN 1 ELSE 0 END) AS Completed,
              SUM(CASE WHEN AwardDataExtra LIKE '1' THEN 1 ELSE 0 END) AS Mastered
              FROM SiteAwards
              WHERE AwardType LIKE '1'
              AND AwardData IN (" . implode(",", $gameIDs) . ")
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
              SUM(CASE WHEN AwardDataExtra LIKE '0' THEN 1 ELSE 0 END) AS Completed,
              SUM(CASE WHEN AwardDataExtra LIKE '1' THEN 1 ELSE 0 END) AS Mastered
              FROM siteawards AS sa
              LEFT JOIN GameData AS gd ON gd.ID = sa.AwardData
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE sa.AwardType LIKE '1'
              AND AwardData IN(" . implode(",", $gameIDs) . ")
              GROUP BY sa.AwardData
              ORDER BY Title";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }
    return $retVal;
}
