<?php

use Illuminate\Support\Str;

function RemovePasswordResetToken(string $username): bool
{
    $db = getMysqliConnection();

    sanitize_sql_inputs($username);

    $query = "UPDATE UserAccounts AS ua SET ua.PasswordResetToken = '' WHERE ua.User='$username'";
    s_mysql_query($query);

    return mysqli_affected_rows($db) >= 1;
}

function isValidPasswordResetToken(string $usernameIn, string $passwordResetToken): bool
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

function RequestPasswordReset(string $usernameIn): bool
{
    sanitize_sql_inputs($usernameIn);

    $userFields = GetUserFields($usernameIn, ["User", "EmailAddress"]);
    if ($userFields == null) {
        return false;
    }

    $username = $userFields["User"];
    $emailAddress = $userFields["EmailAddress"];

    $newToken = Str::random(20);

    s_mysql_query("UPDATE UserAccounts AS ua
              SET ua.PasswordResetToken = '$newToken', Updated=NOW()
              WHERE ua.User='$username'");

    SendPasswordResetEmail($username, $emailAddress, $newToken);

    return true;
}
