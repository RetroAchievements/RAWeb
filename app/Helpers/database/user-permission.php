<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\Comment;
use App\Models\User;
use App\Platform\Events\PlayerRankedStatusChanged;

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

    $targetUser = User::whereName($targetUsername)->first();
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

function setAccountForumPostAuth(User $sourceUser, int $sourcePermissions, User $targetUser, bool $authorize): bool
{
    // $sourceUser is setting $targetUser's forum post permissions.

    if (!$authorize) {
        // This user is a spam user. Remove all their posts and set their account as banned.
        $targetUser->ManuallyVerified = 0;
        $targetUser->forum_verified_at = null;
        $targetUser->saveQuietly();

        // Purge all of the spammer's unauthorized posts.
        $targetUser->forumPosts()->where(function ($query) {
            $query->whereNull('authorized_at')
                ->orWhere('is_authorized', 0);
        })->delete();

        // Also ban the spammy user!
        SetAccountPermissionsJSON($sourceUser->User, $sourcePermissions, $targetUser->User, Permissions::Spam);
        $targetUser->roles()->detach();

        return true;
    }

    // This user is not a spam user. Authorize all their posts and set their account to verified.
    $targetUser->ManuallyVerified = 1;
    $targetUser->forum_verified_at = now();
    $targetUser->saveQuietly();

    authorizeAllForumPostsForUser($targetUser);

    addArticleComment('Server', ArticleType::UserModeration, $targetUser->id,
        $sourceUser->display_name . ' authorized user\'s forum posts'
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
    $user = User::whereName($username)->first();

    if (!$user) {
        return;
    }

    $user->email_verified_at = null;
    $user->password = null;
    $user->SaltedPass = '';
    $user->setAttribute('Permissions', $permissions);
    $user->fbUser = 0;
    $user->fbPrefs = null;
    $user->cookie = null;
    $user->appToken = null;
    $user->appTokenExpiry = null;
    $user->ManuallyVerified = 0;
    $user->forum_verified_at = null;
    $user->Motto = '';
    $user->Untracked = 1;
    $user->unranked_at = now();
    $user->APIKey = null;
    $user->UserWallActive = 0;
    $user->RichPresenceMsg = null;
    $user->RichPresenceMsgDate = null;
    $user->PasswordResetToken = '';
    $user->banned_at = now();
    $user->Updated = now();

    $user->save();

    $userComments = Comment::where('user_id', $user->id)->get();
    $userComments->unsearchable();

    removeAvatar($username);
    $user->subscriptions()->delete();

    PlayerRankedStatusChanged::dispatch($user);
}
