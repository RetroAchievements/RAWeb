<?php
function validateUser(&$user, $pass, &$fbUser, $permissionRequired)
{
    //    Note: avoid this wherever possible!! Requires raw use of user's password!

    if (!isValidUsername($user)) {
        return false;
    }

    $query = "SELECT User, SaltedPass, fbUser, cookie, Permissions FROM UserAccounts WHERE User='$user'";
    $result = s_mysql_query($query);
    if ($result == false) {
        error_log(__FUNCTION__ . " failed: bad query: $query");
        return false;
    } else {
        $row = mysqli_fetch_array($result);

        //    Add salt
        $saltedHash = md5($pass . getenv('RA_PASSWORD_SALT'));

        if ($row['SaltedPass'] == $saltedHash) {
            $fbUser = $row['fbUser'];
            $user = $row['User'];
            return $row['Permissions'] >= $permissionRequired;
        } else {
            error_log(__FUNCTION__ . " failed: passwords don't match for user:$user pass:" . $row['SaltedPass']);
            return false;
        }
    }
}

function validateUser_app(&$user, $token, &$fbUser, $permissionRequired)
{
    $fbUser = 0; //    TBD: Remove!
    return RA_ReadTokenCredentials(
        $user,
        $token,
        $pointsUnused,
        $truePointsUnused,
        $unreadMessagesUnused,
        $permissionsUnused,
        $permissionRequired
    );
}

function validateUser_cookie(&$user, $cookie, $permissionRequired, &$permissions = 0)
{
    return validateFromCookie($user, $points, $permissions, $permissionRequired);
}

function validateFromCookie(&$userOut, &$pointsOut, &$permissionsOut, $permissionRequired = 0)
{
    $userOut = RA_ReadCookie("RA_User");
    $cookie = RA_ReadCookie("RA_Cookie");
    if (mb_strlen($userOut) < 2 || mb_strlen($cookie) < 2 || !isValidUsername($userOut)) {
        //    There is no cookie
        return false;
    } else {
        //    Cookie maybe stale: check it!
        $query = "SELECT User, cookie, RAPoints, Permissions FROM UserAccounts WHERE User='$userOut'";
        $dbResult = s_mysql_query($query);
        if ($dbResult == false) {
            error_log(__FUNCTION__ . " failed: bad query: $query");
            return false;
        } else {
            $data = mysqli_fetch_array($dbResult);
            if ($data['cookie'] == $cookie) {
                $pointsOut = $data['RAPoints'];
                $userOut = $data['User']; //    Case correction
                $permissionsOut = $data['Permissions'];
                return $permissionsOut >= $permissionRequired;
            } else {
                error_log(__FUNCTION__ . " failed: cookie doesn't match for user:$userOut (given: $cookie, should be " . $data['cookie'] . ")");
                return false;
            }
        }
    }
}

function getCookie(&$userOut, &$cookieOut)
{
    $userOut = RA_ReadCookie('RA_User');
    $cookieOut = RA_ReadCookie('RA_Cookie');
}

function RA_ReadCookieCredentials(
    &$userOut,
    &$pointsOut,
    &$truePointsOut,
    &$unreadMessagesOut,
    &$permissionOut,
    $minPermissions = null,
    &$userIDOut = null
) {
    //    Promise some values:
    $userOut = RA_ReadCookie('RA_User');
    $cookie = RA_ReadCookie('RA_Cookie');
    $pointsOut = 0;
    $truePointsOut = 0;
    $unreadMessagesOut = 0;
    $permissionOut = 0;

    if (mb_strlen($userOut) < 2 || mb_strlen($cookie) < 10 || !isValidUsername($userOut)) {
        RA_ClearCookie('RA_User');
        RA_ClearCookie('RA_Cookie');
        $userOut = null;
        //error_log( __FUNCTION__ . " User invalid, bailing..." );
        return false;
    }

    $query = "SELECT ua.cookie, ua.RAPoints, ua.UnreadMessageCount, ua.TrueRAPoints, ua.Permissions, ua.ID
              FROM UserAccounts AS ua
              WHERE User='$userOut'";

    $result = s_mysql_query($query);
    if ($result == false) {
        RA_ClearCookie('RA_User');
        RA_ClearCookie('RA_Cookie');
        $userOut = null;

        //error_log( __FUNCTION__ . " failed: bad query: query:$query" );
        return false;
    } else {
        $dbResult = mysqli_fetch_array($result);
        $serverCookie = $dbResult['cookie'];
        if (strcmp($serverCookie, $cookie) !== 0 || $dbResult['Permissions'] == -1) {
            RA_ClearCookie('RA_User');
            RA_ClearCookie('RA_Cookie');
            $userOut = null;

            //error_log( __FUNCTION__ . " failed: bad cookie: query:$query cookies: local:$cookie server:$serverCookie. Removing it!" );
            return false;
        } else {
            userActivityPing($userOut);

            //    Cookies match: now validate permissions if required
            $pointsOut = $dbResult['RAPoints'];
            $unreadMessagesOut = $dbResult['UnreadMessageCount'];
            $truePointsOut = $dbResult['TrueRAPoints'];
            $permissionOut = $dbResult['Permissions'];
            $userIDOut = $dbResult['ID'];

            //    Only compare if requested, otherwise return true meaning 'logged in'
            if (isset($minPermissions)) {
                return $permissionOut >= $minPermissions;
            } else {
                return true;
            }
        }
    }
}

function RA_ReadTokenCredentials(
    &$userOut,
    $token,
    &$pointsOut,
    &$truePointsOut,
    &$unreadMessagesOut,
    &$permissionOut,
    $permissionRequired = null
) {
    if ($userOut == null || $userOut == '') {
        error_log(__FUNCTION__ . " failed: no user given: $userOut, $token ");
        return false;
    }
    if (!isValidUsername($userOut)) {
        return false;
    }

    $query = "SELECT ua.User, ua.appToken, ua.RAPoints, ua.UnreadMessageCount, ua.TrueRAPoints, ua.Permissions
              FROM UserAccounts AS ua
              WHERE User='$userOut'";
    $result = s_mysql_query($query);
    if ($result == false) {
        error_log(__FUNCTION__ . " failed: bad query: $query");
        return false;
    } else {
        $row = mysqli_fetch_array($result);
        $permissionOut = $row['Permissions'];
        if ($row['appToken'] == $token) {
            $userOut = $row['User']; //    Case correction
            if (isset($permissionRequired)) {
                return $permissionOut >= $permissionRequired;
            } else {
                return true;
            }
        } else {
            error_log(__FUNCTION__ . " failed: passwords don't match for user:$userOut (given: $token, should be " . $row['appToken'] . ")");
            return false;
        }
    }
}

function generateAPIKey($user)
{
    if (!getAccountDetails($user, $userData)) {
        error_log(__FUNCTION__ . " API Key gen fail 1: not a user?");
        return "";
    }

    if ($userData['Permissions'] < 1) {
        error_log(__FUNCTION__ . " API Key gen fail 2: not a full account!");
        return "";
    }

    $newKey = rand_string(32);

    $query = "UPDATE UserAccounts AS ua
              SET ua.APIKey='$newKey', Updated=NOW()
              WHERE ua.User = '$user'";

    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        log_email(__FUNCTION__ . " API Key gen fail 3: sql fail?!");
        error_log(__FUNCTION__ . " API Key gen fail 3: sql fail?!");
        return "";
    }

    return $newKey;
}

function GetAPIKey($user)
{
    if (!isValidUsername($user)) {
        return false;
    }

    $query = "SELECT APIKey FROM UserAccounts AS ua
        WHERE ua.User = '$user' AND ua.Permissions >= 1";

    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        error_log(__FUNCTION__);
        error_log("errors fetching API Key for $user!");
        log_email(__FUNCTION__ . " cannot fetch API key for $user");
        return "No API Key found!";
    } else {
        $db_entry = mysqli_fetch_assoc($dbResult);
        return $db_entry['APIKey'];
    }
}

function LogSuccessfulAPIAccess($user)
{
    $query = "UPDATE UserAccounts AS ua
              SET ua.APIUses=ua.APIUses+1
              WHERE ua.User = '$user' ";

    s_mysql_query($query);
}

function ValidateAPIKey($user, $key)
{
    if (mb_strlen($key) < 20 || !isValidUsername($user)) {
        return false;
    }

    $query = "SELECT COUNT(*)
              FROM UserAccounts AS ua
              WHERE ua.User = '$user' AND ua.Permissions >= 1 AND ua.APIKey = '$key' ";

    $dbResult = s_mysql_query($query);

    if ($dbResult == false) {
        error_log(__FUNCTION__);
        error_log("errors validating API Key for $user (given: $key)!");
        log_email(__FUNCTION__ . " errors validating API Key for $user (given: $key)!");
        return false;
    }

    LogSuccessfulAPIAccess($user);

    $data = mysqli_fetch_assoc($dbResult);
    return $data['COUNT(*)'] != 0;
}

function RemovePasswordResetToken($username, $passwordResetToken)
{
    global $db;

    $query = "UPDATE UserAccounts AS ua "
        . "WHERE ua.User='$user' "
        . "SET ua.PasswordResetToken = ''";

    $dbResult = s_mysql_query($query);
    return mysqli_affected_rows($db) == 1;
}

function isValidPasswordResetToken($usernameIn, $passwordResetToken)
{
    global $db;

    $retVal = [];

    if (mb_strlen($passwordResetToken) == 20) {
        $query = "SELECT * FROM UserAccounts AS ua "
            . "WHERE ua.User='$usernameIn' AND ua.PasswordResetToken='$passwordResetToken'";

        $dbResult = s_mysql_query($query);
        SQL_ASSERT($dbResult);

        if (mysqli_num_fields($dbResult) == 1) {
            //    Success; delete old token
            //RemovePasswordResetToken( $usernameIn, $passwordResetToken );
            $retVal['Success'] = true;
        } else {
            $retVal['Error'] = "Incorrect token.";
            $retVal['Success'] = false;
        }
    } else {
        $retVal['Error'] = "Token looks to be invalid. Must be 20 characters.";
        $retVal['Success'] = false;
    }

    return $retVal;
}

function RequestPasswordReset($usernameIn)
{
    global $db;

    $retVal = [];

    $userFields = GetUserFields(mysqli_real_escape_string($db, $usernameIn), ["User", "EmailAddress"]);
    if ($userFields == null) {
        $retVal['Error'] = "Could not find $usernameIn";
        return $retVal;
    }

    $username = $userFields["User"];
    $emailAddress = $userFields["EmailAddress"];

    $newToken = rand_string(20);

    $query = "UPDATE UserAccounts AS ua
              SET ua.PasswordResetToken = '$newToken', Updated=NOW()
              WHERE ua.User='$username'";

    $dbResult = s_mysql_query($query);
    SQL_ASSERT($dbResult);

    SendPasswordResetEmail($username, $emailAddress, $newToken);

    $retVal['Success'] = true;

    return $retVal;
}
