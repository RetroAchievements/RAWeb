<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Enums\Permissions;
use App\Models\AchievementSetClaim;
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

    // Junior developers can have claims in review.
    // When being promoted from Junior Developer, change any In Review claims to Active.
    if (
        $targetUserCurrentPermissions === Permissions::JuniorDeveloper
        && $targetUserNewPermissions > Permissions::JuniorDeveloper
    ) {
        $targetUserNewPermissionsString = Permissions::toString($targetUserNewPermissions);
        $comment = "$actingUsername updated $targetUsername's claim via promotion to $targetUserNewPermissionsString. Claim Status: " . ClaimStatus::toString(ClaimStatus::Active);

        $inReviewClaims = AchievementSetClaim::where('User', $targetUsername)
            ->where('Status', ClaimStatus::InReview)->get();
        foreach ($inReviewClaims as $claim) {
            $claim->Status = ClaimStatus::Active;
            $claim->save();

            addArticleComment('Server', ArticleType::SetClaim, $claim->GameID, $comment);
        }
    }

    // If the user loses developer permissions, drop all claims held by the user.
    if (
        $targetUserCurrentPermissions >= Permissions::JuniorDeveloper
        && $targetUserNewPermissions < Permissions::JuniorDeveloper
    ) {
        $targetUserNewPermissionsString = Permissions::toString($targetUserNewPermissions);

        $targetUserClaims = $targetUser->achievementSetClaims()->get();
        foreach ($targetUserClaims as $claim) {
            $claim->Status = ClaimStatus::Dropped;
            $claim->save();

            $comment = "$actingUsername dropped $targetUsername's " . ClaimType::toString($claim->ClaimType) . " claim via demotion to $targetUserNewPermissionsString.";
            addArticleComment('Server', ArticleType::SetClaim, $claim->GameID, $comment);
        }
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

function setAccountForumPostAuth(User $sourceUser, int $sourcePermissions, User $targetUser, bool $authorize): bool
{
    // $sourceUser is setting $targetUser's forum post permissions.

    if (!$authorize) {
        // This user is a spam user. Remove all their posts and set their account as banned.
        $targetUser->ManuallyVerified = 0;
        $targetUser->forum_verified_at = null;
        $targetUser->save();

        // Purge all of the spammer's unauthorized posts.
        $targetUser->forumPosts()->where(function ($query) {
            $query->whereNull('authorized_at')
                ->orWhere('Authorised', 0);
        })->delete();

        // Also ban the spammy user!
        SetAccountPermissionsJSON($sourceUser->User, $sourcePermissions, $targetUser->User, Permissions::Spam);

        return true;
    }

    // This user is not a spam user. Authorize all their posts and set their account to verified.
    $targetUser->ManuallyVerified = 1;
    $targetUser->forum_verified_at = now();
    $targetUser->save();

    authorizeAllForumPostsForUser($targetUser->User);

    addArticleComment('Server', ArticleType::UserModeration, $sourceUser->id,
        $sourceUser->User . ' authorized user\'s forum posts'
    );

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
