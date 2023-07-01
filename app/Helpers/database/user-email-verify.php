<?php

use App\Site\Enums\Permissions;
use Illuminate\Support\Str;

function generateEmailVerificationToken(string $user): ?string
{
    $emailCookie = Str::random(16);
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

function validateEmailVerificationToken(string $emailCookie, ?string &$user): bool
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

function deleteExpiredEmailVerificationTokens(): bool
{
    return (bool) s_mysql_query("DELETE FROM EmailConfirmations WHERE Expires <= DATE(NOW()) ORDER BY Expires DESC");
}
