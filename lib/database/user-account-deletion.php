<?php

use RA\ArticleType;
use RA\Permissions;

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
