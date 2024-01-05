<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Community\Models\AchievementSetClaim;
use App\Site\Enums\Permissions;

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
    string $actingUser,
    int $actingUserPermissions,
    string $targetUser,
    int $targetUserNewPermissions
): array {
    $retVal = [];
    sanitize_sql_inputs($actingUser, $targetUser);

    $targetUserData = [];
    if (!getAccountDetails($targetUser, $targetUserData)) {
        $retVal['Success'] = false;
        $retVal['Error'] = "$targetUser not found";
    }

    $targetUserCurrentPermissions = $targetUserData['Permissions'];

    $retVal = [
        'DestUser' => $targetUser,
        'DestPrevPermissions' => $targetUserCurrentPermissions,
        'NewPermissions' => $targetUserNewPermissions,
    ];

    $permissionChangeAllowed = true;

    // only admins can change permissions
    if ($actingUserPermissions < Permissions::Moderator) {
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

    if ($targetUserNewPermissions === $targetUserCurrentPermissions) {
        $retVal['Success'] = true;

        return $retVal;
    }

    $query = "UPDATE UserAccounts SET Permissions = $targetUserNewPermissions, Updated=NOW() WHERE User='$targetUser'";
    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        $retVal['Success'] = false;
        $retVal['Error'] = "$actingUser ($actingUserPermissions) is trying to set $targetUser ($targetUserCurrentPermissions) to $targetUserNewPermissions??! Cannot find user: '$targetUser'!";

        return $retVal;
    }

    if ($targetUserNewPermissions < Permissions::Unregistered) {
        banAccountByUsername($targetUser, $targetUserNewPermissions);
    }

    /* Junior developers can have claims in review. When being promoted from Junior Developer,
     * change any In Review claims to Active. */
    if ($targetUserCurrentPermissions === Permissions::JuniorDeveloper
        && $targetUserNewPermissions > Permissions::JuniorDeveloper) {
        $targetUserNewPermissionsString = Permissions::toString($targetUserNewPermissions);
        $comment = "$actingUser updated $targetUser's claim via promotion to $targetUserNewPermissionsString. Claim Status: " . ClaimStatus::toString(ClaimStatus::Active);

        $inReviewClaims = AchievementSetClaim::where('User', $targetUser)
            ->where('Status', ClaimStatus::InReview)->get();
        foreach ($inReviewClaims as $claim) {
            $claim->Status = ClaimStatus::Active;
            $claim->save();

            addArticleComment('Server', ArticleType::SetClaim, $claim->GameID, $comment);
        }
    }

    /* If the user loses developer permissions, drop all claims held by the user */
    if ($targetUserCurrentPermissions >= Permissions::JuniorDeveloper
        && $targetUserNewPermissions < Permissions::JuniorDeveloper) {
        $targetUserNewPermissionsString = Permissions::toString($targetUserNewPermissions);

        foreach (AchievementSetClaim::where('User', $targetUser)->get() as $claim) {
            $claim->Status = ClaimStatus::Dropped;
            $claim->save();

            $comment = "$actingUser dropped $targetUser's " . ClaimType::toString($claim->ClaimType) . " claim via demotion to $targetUserNewPermissionsString.";
            addArticleComment('Server', ArticleType::SetClaim, $claim->GameID, $comment);
        }
    }

    $retVal['Success'] = true;

    addArticleComment('Server', ArticleType::UserModeration, $targetUserData['ID'],
        $actingUser . ' set account type to ' . Permissions::toString($targetUserNewPermissions)
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
        $query = "UPDATE UserAccounts SET ManuallyVerified = 0, Updated=NOW() WHERE User='$user'";
        $dbResult = s_mysql_query($query);
        if (!$dbResult) {
            return false;
        }

        // Also ban the spammy user!
        RemoveUnauthorisedForumPosts($user);

        SetAccountPermissionsJSON($sourceUser, $sourcePermissions, $user, Permissions::Spam);

        return true;
    }

    $query = "UPDATE UserAccounts SET ManuallyVerified = 1, Updated=NOW() WHERE User='$user'";
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
        WHERE u.User='$username'"
    );
    if (!$dbResult) {
        echo mysqli_error($db) . PHP_EOL;
    }

    removeAvatar($username);

    echo "SUCCESS" . PHP_EOL;
}
