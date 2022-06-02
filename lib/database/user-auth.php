<?php

use RA\ActivityType;
use RA\Permissions;

function authenticateFromPasswordOrAppToken($user, $pass, $token): array
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

/*
 * PASSWORD
 */

function authenticateFromPassword(&$user, $pass): bool
{
    if (!isValidUsername($user)) {
        return false;
    }

    sanitize_sql_inputs($user);

    $query = "SELECT User, Password, SaltedPass, cookie, Permissions FROM UserAccounts WHERE User='$user'";
    $result = s_mysql_query($query);
    if (!$result) {
        return false;
    }

    $row = mysqli_fetch_array($result);

    if (!$row) {
        return false;
    }

    $hashedPassword = $row['Password'];

    if (mb_strlen($row['SaltedPass']) === 32) {
        $pepperedPassword = md5($pass . getenv('RA_PASSWORD_SALT'));
        if ($row['SaltedPass'] !== $pepperedPassword) {
            return false;
        }
        $hashedPassword = migratePassword($user, $pass);
    }

    if (!password_verify($pass, $hashedPassword)) {
        return false;
    }

    $user = $row['User'];

    return $row['Permissions'] >= Permissions::Unregistered;
}

function changePassword($user, $pass): bool
{
    sanitize_sql_inputs($user);

    $hashedPassword = hashPassword($pass);
    $query = "UPDATE UserAccounts SET Password='$hashedPassword', SaltedPass='', Updated=NOW() WHERE user='$user'";
    if (s_mysql_query($query) === false) {
        log_sql_fail();

        return false;
    }

    return true;
}

function hashPassword($pass): string
{
    return password_hash($pass, PASSWORD_ARGON2ID, [
        'memory_cost' => 1024,
        'threads' => 2,
        'time' => 1,
    ]);
}

function migratePassword($user, $pass): string
{
    $hashedPassword = hashPassword($pass);
    s_mysql_query("UPDATE UserAccounts SET Password='$hashedPassword', SaltedPass='' WHERE User='$user'");

    return $hashedPassword;
}

/*
 * COOKIE
 */

function authenticateFromCookie(
    ?string &$userOut,
    ?int &$permissionsOut,
    ?array &$userDetailsOut,
    ?int $minPermissions = null
): bool {
    $userOut = null;
    $permissionsOut = Permissions::Unregistered;

    // RA_User cookie no longer used, clear it out for security purposes
    if (cookieExists('RA_User')) {
        clearCookie('RA_User');
    }

    $cookie = readCookie('RA_Cookie');
    if ($userDetailsOut = getAccountDetailsFromCookie($cookie)) {
        $userOut = $userDetailsOut['User'];
        $permissionsOut = (int) $userDetailsOut['Permissions'];

        if ($permissionsOut !== Permissions::Banned) {
            // valid active account. update the last activity timestamp
            userActivityPing($userOut);

            // validate permissions for the current page if required
            if (isset($minPermissions)) {
                return $permissionsOut >= $minPermissions;
            }

            // return true meaning 'logged in'
            return true;
        }
    }

    // invalid credentials, clear the cookies and return failure
    clearCookie('RA_Cookie');

    $userDetailsOut = null;
    return false;
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
    applyCookie('RA_Cookie', $cookie, $expiry, true);

    return true;
}

/*
 * TOKEN
 */

function authenticateFromAppToken(
    &$userOut,
    $token,
    &$permissionOut
): bool {
    if ($userOut == null || $userOut == '') {
        return false;
    }
    if (!isValidUsername($userOut)) {
        return false;
    }
    if (empty($token)) {
        return false;
    }

    sanitize_sql_inputs($userOut);

    $query = "SELECT ua.User, ua.appToken, ua.RAPoints, ua.UnreadMessageCount, ua.TrueRAPoints, ua.Permissions
              FROM UserAccounts AS ua
              WHERE User='$userOut'";
    $result = s_mysql_query($query);
    if ($result) {
        $row = mysqli_fetch_array($result);
        $permissionOut = (int) $row['Permissions'];
        if ($row['appToken'] === $token) {
            $userOut = $row['User']; // Case correction

            return true;
        }
    }

    return false;
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

/*
 * WEB API Key
 */

function generateAPIKey($user): string
{
    sanitize_sql_inputs($user);

    if (!getAccountDetails($user, $userData)) {
        return "";
    }

    if ($userData['Permissions'] < Permissions::Registered) {
        return "";
    }

    $newKey = rand_string(32);

    $query = "UPDATE UserAccounts AS ua
              SET ua.APIKey='$newKey', Updated=NOW()
              WHERE ua.User = '$user'";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return "";
    }

    return $newKey;
}

function GetAPIKey($user): ?string
{
    sanitize_sql_inputs($user);

    if (!isValidUsername($user)) {
        return null;
    }

    $query = "SELECT APIKey FROM UserAccounts AS ua
        WHERE ua.User = '$user' AND ua.Permissions >= " . Permissions::Registered;

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return null;
    } else {
        $db_entry = mysqli_fetch_assoc($dbResult);

        return $db_entry['APIKey'];
    }
}

function LogSuccessfulAPIAccess($user): void
{
    sanitize_sql_inputs($user);

    $query = "UPDATE UserAccounts AS ua
              SET ua.APIUses=ua.APIUses+1
              WHERE ua.User = '$user' ";

    s_mysql_query($query);
}

function ValidateAPIKey($user, $key): bool
{
    sanitize_sql_inputs($user, $key);

    if (mb_strlen($key) < 20 || !isValidUsername($user)) {
        return false;
    }

    $query = "SELECT COUNT(*)
              FROM UserAccounts AS ua
              WHERE ua.User = '$user' AND ua.Permissions >= 1 AND ua.APIKey = '$key' ";

    $dbResult = s_mysql_query($query);

    if (!$dbResult) {
        // errors validating API Key for $user (given: $key)
        return false;
    }

    LogSuccessfulAPIAccess($user);

    $data = mysqli_fetch_assoc($dbResult);

    return $data['COUNT(*)'] != 0;
}
