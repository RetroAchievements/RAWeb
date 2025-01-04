<?php

use App\Models\User;
use Illuminate\Support\Str;

/**
 * @deprecated replace with Laravel standard features and/or Fortify
 */
function isValidPasswordResetToken(string $usernameIn, string $passwordResetToken): bool
{
    sanitize_sql_inputs($usernameIn, $passwordResetToken);

    if (mb_strlen($passwordResetToken) == 20) {
        $query = "SELECT * FROM UserAccounts AS ua "
            . "WHERE (ua.User='$usernameIn' OR ua.display_name='$usernameIn') "
            . "AND ua.PasswordResetToken='$passwordResetToken'";

        $dbResult = s_mysql_query($query);

        if (mysqli_num_rows($dbResult) == 1) {
            return true;
        }
    }

    return false;
}

/**
 * @deprecated replace with Laravel standard features and/or Fortify
 */
function RequestPasswordReset(User $user): bool
{
    $username = $user->display_name;
    $emailAddress = $user->EmailAddress;

    $newToken = Str::random(20);

    s_mysql_query("UPDATE UserAccounts AS ua
              SET ua.PasswordResetToken = '$newToken', Updated=NOW()
              WHERE ua.User='$username' OR ua.display_name='$username'");

    SendPasswordResetEmail($username, $emailAddress, $newToken);

    return true;
}
