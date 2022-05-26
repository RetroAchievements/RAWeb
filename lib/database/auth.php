<?php

use RA\Permissions;

function authenticateFromPassword(&$user, $pass): bool
{
    if (!isValidUsername($user)) {
        return false;
    }

    sanitize_sql_inputs($user);

    $query = "SELECT User, Password, SaltedPass, fbUser, cookie, Permissions FROM UserAccounts WHERE User='$user'";
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

function authenticateFromCookie(
    ?string &$userOut,
    ?int &$permissionsOut,
    ?array &$userDetailsOut,
    ?int $minPermissions = null
): bool {
    $userOut = null;
    $permissionsOut = Permissions::Unregistered;

    // RA_User cookie no longer used, clear it out for security purposes
    if (RA_CookieExists('RA_User')) {
        RA_ClearCookie('RA_User');
    }

    $cookie = RA_ReadCookie('RA_Cookie');
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
    RA_ClearCookie('RA_Cookie');

    $userDetailsOut = null;
    return false;
}

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

function RemovePasswordResetToken($username): bool
{
    global $db;

    sanitize_sql_inputs($username);

    $query = "UPDATE UserAccounts AS ua SET ua.PasswordResetToken = '' WHERE ua.User='$username'";
    s_mysql_query($query);

    return mysqli_affected_rows($db) >= 1;
}

function isValidPasswordResetToken($usernameIn, $passwordResetToken): bool
{
    sanitize_sql_inputs($usernameIn, $passwordResetToken);

    if (mb_strlen($passwordResetToken) == 20) {
        $query = "SELECT * FROM UserAccounts AS ua "
            . "WHERE ua.User='$usernameIn' AND ua.PasswordResetToken='$passwordResetToken'";

        $dbResult = s_mysql_query($query);

        if (mysqli_num_rows($dbResult) == 1) {
            return true;
        }
    }

    return false;
}

function RequestPasswordReset($usernameIn): bool
{
    sanitize_sql_inputs($usernameIn);

    $userFields = GetUserFields($usernameIn, ["User", "EmailAddress"]);
    if ($userFields == null) {
        return false;
    }

    $username = $userFields["User"];
    $emailAddress = $userFields["EmailAddress"];

    $newToken = rand_string(20);

    $query = "UPDATE UserAccounts AS ua
              SET ua.PasswordResetToken = '$newToken', Updated=NOW()
              WHERE ua.User='$username'";

    $dbResult = s_mysql_query($query);

    SendPasswordResetEmail($username, $emailAddress, $newToken);

    return true;
}
