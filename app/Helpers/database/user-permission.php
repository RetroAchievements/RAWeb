<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\User;

function getUserPermissions(?string $user): int
{
    if ($user == null) {
        return 0;
    }

    $query = "SELECT Permissions FROM UserAccounts WHERE User=:user";
    $row = legacyDbFetch($query, ['user' => $user]);

    return $row ? (int) $row['Permissions'] : Permissions::Unregistered;
}

function SetAccountPermissionsJSON(
    string $actingUsername,
    int $actingUserPermissions,
    string $targetUsername,
    int $targetUserNewPermissions
): array {
    $retVal = [];

    $targetUser = User::firstWhere('User', $targetUsername);
    if (!$targetUser) {
        $retVal['Success'] = false;
        $retVal['Error'] = "$targetUsername not found";
    }

    sanitize_sql_inputs($actingUsername, $targetUsername);

    $targetUserCurrentPermissions = (int) $targetUser->getAttribute('Permissions');

    $retVal = [
        'DestUser' => $targetUsername,
        'DestPrevPermissions' => $targetUserCurrentPermissions,
        'NewPermissions' => $targetUserNewPermissions,
    ];

    $permissionChangeAllowed = true;

    // Only moderators can change another user's permissions.
    if ($actingUserPermissions < Permissions::Moderator) {
        $permissionChangeAllowed = false;
    }

    // Do not act on users on same or above level.
    if ($targetUserCurrentPermissions >= $actingUserPermissions) {
        $permissionChangeAllowed = false;
    }

    // Do not allow to set role to same or above level.
    if ($targetUserNewPermissions >= $actingUserPermissions) {
        $permissionChangeAllowed = false;
    }

    if (!$permissionChangeAllowed) {
        $retVal['Success'] = false;
        $retVal['Error'] = "$actingUsername ($actingUserPermissions) is trying to set $targetUsername ($targetUserCurrentPermissions) to $targetUserNewPermissions??! Not allowed!";

        return $retVal;
    }

    if ($targetUserNewPermissions === $targetUserCurrentPermissions) {
        $retVal['Success'] = true;

        return $retVal;
    }

    // If the user is being unbanned, clear their `banned_at` timestamp.
    if (
        $targetUserCurrentPermissions < Permissions::Unregistered
        && $targetUserNewPermissions >= Permissions::Unregistered
    ) {
        $targetUser->banned_at = null;
    }
    // Write the new permissions.
    $targetUser->Permissions = $targetUserNewPermissions;

    $targetUser->save();

    if ($targetUserNewPermissions < Permissions::Unregistered) {
        banAccountByUsername($targetUsername, $targetUserNewPermissions);
    }

    if ($targetUserNewPermissions !== $targetUserCurrentPermissions) {
        updateClaimsForPermissionChange($targetUser, $targetUserNewPermissions, $targetUserCurrentPermissions, $actingUsername);
    }

    $retVal['Success'] = true;

    addArticleComment('Server', ArticleType::UserModeration, $targetUser->id,
        $actingUsername . ' set account type to ' . Permissions::toString($targetUserNewPermissions)
    );

    return $retVal;
}

/*
 * Manual verification / authorize user to post in forums
 */

function getUserForumPostAuth(string $user): bool
{
    sanitize_sql_inputs($user);

    $query = "SELECT uc.ManuallyVerified FROM UserAccounts AS uc WHERE uc.User = '$user'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);

        return (bool) $data['ManuallyVerified'];
    }

    return false;
}

function setAccountForumPostAuth(string $sourceUser, int $sourcePermissions, string $user, bool $authorize): bool
{
    sanitize_sql_inputs($user, $authorize);

    // $sourceUser is setting $user's forum post permissions.

    if (!$authorize) {
        // This user is a spam user: remove all their posts and set their account as banned.
        $query = "UPDATE UserAccounts SET ManuallyVerified = 0, forum_verified_at = null, Updated=NOW() WHERE User='$user'";
        $dbResult = s_mysql_query($query);
        if (!$dbResult) {
            return false;
        }

        // Also ban the spammy user!
        RemoveUnauthorisedForumPosts($user);

        SetAccountPermissionsJSON($sourceUser, $sourcePermissions, $user, Permissions::Spam);

        return true;
    }

    $query = "UPDATE UserAccounts SET ManuallyVerified = 1, forum_verified_at = NOW(), Updated=NOW() WHERE User='$user'";
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

/**
 * APIKey doesn't have to be reset -> permission >= Registered
 *
 * @deprecated TODO move to filament user management
 */
function banAccountByUsername(string $username, int $permissions): void
{
    $db = getMysqliConnection();

    echo "BANNING $username ... ";

    if (empty($username)) {
        echo "FAIL" . PHP_EOL;

        return;
    }

    $dbResult = s_mysql_query("UPDATE UserAccounts u SET
        u.email_verified_at = null,
        u.Password = null,
        u.SaltedPass = '',
        u.Permissions = $permissions,
        u.fbUser = 0,
        u.fbPrefs = null,
        u.cookie = null,
        u.appToken = null,
        u.appTokenExpiry = null,
        u.ManuallyVerified = 0,
        u.forum_verified_at = null,
        u.Motto = '',
        u.Untracked = 1,
        u.APIKey = null,
        u.UserWallActive = 0,
        u.RichPresenceMsg = null,
        u.RichPresenceMsgDate = null,
        u.PasswordResetToken = '',
        u.banned_at = NOW(),
        u.Updated = NOW()
        WHERE u.User='$username'"
    );
    if (!$dbResult) {
        echo mysqli_error($db) . PHP_EOL;
    }

    removeAvatar($username);

    echo "SUCCESS" . PHP_EOL;
}
