<?php

use App\Community\Enums\ArticleType;
use App\Platform\Actions\ResetPlayerAchievementAction;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

function getDeleteDate(string|Carbon $deleteRequested): string
{
    if (empty($deleteRequested)) {
        return '';
    }

    if (!$deleteRequested instanceof Carbon) {
        $deleteRequested = Carbon::parse($deleteRequested);
    }

    return $deleteRequested->addDays(14)->format('Y-m-d');
}

function cancelDeleteRequest(string $username): bool
{
    $user = [];
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

function deleteRequest(string $username, ?string $date = null): bool
{
    $user = [];
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
    $threshold = Carbon::today()->setTime(8, 0)->subWeeks(2);

    /** @var Collection<int, User> $users */
    $users = User::where('DeleteRequested', '<=', $threshold)
        ->orderBy('DeleteRequested')
        ->get();

    foreach ($users as $user) {
        clearAccountData($user);
    }
}

function clearAccountData(User $user): void
{
    // disable account access while we destroy it (prevents creating new records during delete)
    legacyDbStatement("UPDATE UserAccounts u SET
        u.Password = null,
        u.SaltedPass = '',
        u.cookie = null,
        u.appToken = null,
        u.APIKey = null
        WHERE u.ID = :userId", ['userId' => $user->ID]
    );

    // reset all achievements earned by the player
    $action = new ResetPlayerAchievementAction();
    $action->execute($user);

    // TODO $user->activities()->delete();
    legacyDbStatement('DELETE FROM Activity WHERE User = :username', ['username' => $user->User]);
    // TODO $user->achievementUnlocks()->delete();
    legacyDbStatement('DELETE FROM Awarded WHERE User = :username', ['username' => $user->User]);
    // TODO $user->emailConfirmations()->delete();
    legacyDbStatement('DELETE FROM EmailConfirmations WHERE User = :username', ['username' => $user->User]);
    // TODO $user->followers()->delete();
    // TODO $user->following()->delete();
    legacyDbStatement('DELETE FROM Friends WHERE User = :username OR Friend = :friendUsername', ['username' => $user->User, 'friendUsername' => $user->User]);
    // TODO $user->ratings()->delete();
    legacyDbStatement('DELETE FROM Rating WHERE User = :username', ['username' => $user->User]);
    // TODO $user->achievementSetRequests()->delete();
    legacyDbStatement('DELETE FROM SetRequest WHERE User = :username', ['username' => $user->User]);
    // TODO $user->badges()->delete();
    legacyDbStatement('DELETE FROM SiteAwards WHERE User = :username', ['username' => $user->User]);
    // TODO $user->subscriptions()->delete();
    legacyDbStatement('DELETE FROM Subscription WHERE UserID = :userId', ['userId' => $user->ID]);

    legacyDbStatement("UPDATE UserAccounts u SET
        u.Password = null,
        u.SaltedPass = '',
        u.EmailAddress = '',
        u.Permissions = :permissions,
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
        WHERE ID = :userId",
        [
            // Cap permissions to 0 - negative values may stay
            'permissions' => min($user->Permissions, Permissions::Unregistered),
            'userId' => $user->ID,
        ]
    );

    removeAvatar($user->User);

    Log::info("Cleared account data: {$user->User} [{$user->ID}]");
}
