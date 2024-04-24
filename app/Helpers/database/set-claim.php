<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\ClaimFilters;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSorting;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Enums\Permissions;
use App\Models\AchievementSetClaim;
use App\Models\User;
use App\Support\Cache\CacheKey;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Checks if the user is able to make a claim and inserts the claim into the database. If the claim
 * is a collaboration claim then the claim will have the same Finished time as the primary claim for the game.
 */
function insertClaim(User $user, int $gameId, int $claimType, int $setType, int $special): bool
{
    $userPermissions = (int) $user->getAttribute('Permissions');

    $finishedAt = Carbon::now()->addMonths(3); // Default to 3 months from now.

    if ($claimType !== ClaimType::Primary) {
        $primaryClaimFinishTime = AchievementSetClaim::where('game_id', $gameId)
            ->where('Status', ClaimStatus::Active)
            ->where('ClaimType', ClaimType::Primary)
            ->value('Finished');

        // If a primary claim doesn't exist, treat the new claim as a primary claim.
        if ($primaryClaimFinishTime) {
            $finishedAt = $primaryClaimFinishTime;
            $special = ClaimSpecial::None;
        }
    } elseif ($special === ClaimSpecial::None) {
        // Different roles are allowed a maximum number of active primary claims.
        // Does this user currently have fewer primary claims than that maximum?
        $isUserAllowedToClaim = getActiveClaimCount($user->User, false) < permissionsToClaim($userPermissions);

        if (!$isUserAllowedToClaim) {
            return false;
        }
    }

    // Create the claim.
    AchievementSetClaim::create([
        'User' => $user->User,
        'user_id' => $user->id,
        'game_id' => $gameId,
        'ClaimType' => $claimType,
        'SetType' => $setType,
        'Status' => ClaimStatus::Active,
        'Extension' => 0,
        'Special' => $special,
        'Finished' => $finishedAt,
    ]);

    return true;
}

/**
 * Checks if the user already has the game claimed. Allows for checking primary/collaboration claims as well as set type.
 */
function hasSetClaimed(string $username, int $gameID, bool $isPrimaryClaim = false, ?int $setType = null): bool
{
    $bindings = [
        'username' => $username,
        'gameId' => $gameID,
    ];

    $claimTypeCondition = '';
    if ($isPrimaryClaim) {
        $bindings['claimType'] = ClaimType::Primary;
        $claimTypeCondition = 'AND ClaimType = :claimType';
    }

    $setTypeCondition = '';
    if (isset($setType)) {
        $bindings['setType'] = $setType;
        $setTypeCondition = 'AND SetType = :setType';
    }

    $query = "
        SELECT
            COUNT(*) AS claimCount
        FROM
            SetClaim
        WHERE
            Status IN (" . ClaimStatus::Active . ',' . ClaimStatus::InReview . ")
            $claimTypeCondition
            $setTypeCondition
            AND User = :username
            AND game_id = :gameId";

    return legacyDbFetch($query, $bindings)['claimCount'] > 0;
}

/**
 * Marks a claim as complete after verifying that the user completing the claim
 * has the primary claim on the game. Any collaboration claims will also be
 * marked as complete.
 */
function completeClaim(string $user, int $gameID): bool
{
    // Only allow primary claim user to mark a claim as complete
    if (hasSetClaimed($user, $gameID, true)) {
        $now = Carbon::now();

        // cannot complete In Review claim
        $query = "
            UPDATE
                SetClaim
            SET
                Status = " . ClaimStatus::Complete . ",
                Finished = '$now',
                Updated = '$now'
            WHERE
                Status = " . ClaimStatus::Active . "
                AND game_id = '$gameID'";

        return legacyDbStatement($query);
    }

    return false;
}

/**
 * Marks a claim as dropped.
 */
function dropClaim(string $user, int $gameID): bool
{
    $now = Carbon::now();

    // cannot drop In Review claim
    $query = "
        UPDATE
            SetClaim
        SET
            Status =  " . ClaimStatus::Dropped . ",
            Finished = '$now',
            Updated = '$now'
        WHERE
            Status = " . ClaimStatus::Active . "
            AND User = '$user'
            AND game_id = '$gameID'";

    return legacyDbStatement($query);
}

function updateClaimsForPermissionChange(User $user, int $permissionsAfter, int $permissionsBefore, ?string $actingUsername = null): void
{
    // Junior developers can have claims in review.
    // When being promoted from Junior Developer, change any In Review claims to Active.
    if ($permissionsBefore === Permissions::JuniorDeveloper && $permissionsAfter > Permissions::JuniorDeveloper) {
        $permissionsString = Permissions::toString($permissionsAfter);
        if (!empty($actingUsername)) {
            $comment = "$actingUsername updated {$user->User}'s claim via promotion to $permissionsString.";
        } else {
            $comment = "{$user->User}'s claim updated via promotion to $permissionsString";
        }
        $comment .= " Claim Status: " . ClaimStatus::toString(ClaimStatus::Active);

        $inReviewClaims = $user->achievementSetClaims()
            ->where('Status', ClaimStatus::InReview)->get();
        foreach ($inReviewClaims as $claim) {
            $claim->Status = ClaimStatus::Active;
            $claim->save();

            addArticleComment('Server', ArticleType::SetClaim, $claim->game_id, $comment);
        }
    }

    // If the user loses developer permissions, drop all claims held by the user.
    if ($permissionsBefore >= Permissions::JuniorDeveloper && $permissionsAfter < Permissions::JuniorDeveloper) {
        $permissionsString = Permissions::toString($permissionsAfter);

        $activeClaims = $user->achievementSetClaims()
            ->whereIn('Status', [ClaimStatus::Active, ClaimStatus::InReview])->get();
        foreach ($activeClaims as $claim) {
            $claim->Status = ClaimStatus::Dropped;
            $claim->save();

            if (!empty($actingUsername)) {
                $comment = "$actingUsername dropped {$user->User}'s " . ClaimType::toString($claim->ClaimType) . " claim via demotion to $permissionsString.";
            } else {
                $comment = "{$user->User}'s " . ClaimType::toString($claim->ClaimType) . " claim dropped via demotion to $permissionsString.";
            }

            addArticleComment('Server', ArticleType::SetClaim, $claim->game_id, $comment);
        }
    }
}

/**
 * Extends a claim a months beyone it's initial expiration time if it expires withing a week.
 * Any collaboration claims will be extended as well.
 */
function extendClaim(string $user, int $gameID): bool
{
    if (hasSetClaimed($user, $gameID, true)) {
        $query = "
            UPDATE
                SetClaim
            SET
                Extension = Extension + 1,
                Finished = DATE_ADD(Finished, INTERVAL 3 MONTH),
                Updated = NOW()
            WHERE
                Status IN (" . ClaimStatus::Active . ',' . ClaimStatus::InReview . ")
                AND game_id = '$gameID'
                AND TIMESTAMPDIFF(MINUTE, NOW(), Finished) <= 10080"; // 7 days = 7 * 24 * 60

        if (s_mysql_query($query)) {
            Cache::forget(CacheKey::buildUserExpiringClaimsCacheKey($user));

            return true;
        }
    }

    return false;
}

/**
 * Gets the claim data for a specific game to display to the users.
 */
function getClaimData(int|array $gameID, bool $getFullData = true): array
{
    $query = "SELECT sc.User as User, sc.SetType as SetType, sc.game_id as GameID,
        sc.ClaimType as ClaimType, sc.Created as Created, sc.Finished as Expiration";

    if ($getFullData) {
        $query .= ", sc.Status as Status, sc.ID, ";
        $query .= diffMinutesRemainingStatement('sc.Finished', 'MinutesLeft') . ",";
        $query .= diffMinutesPassedStatement('sc.Created', 'MinutesActive');
    }

    if (is_array($gameID)) {
        if (empty($gameID)) {
            return [];
        }
        $gameIDs = implode(',', $gameID);
    } else {
        $gameIDs = $gameID;
    }

    $query .= " FROM SetClaim sc WHERE sc.game_id IN ($gameIDs)
                 AND sc.Status IN (" . ClaimStatus::Active . "," . ClaimStatus::InReview . ")
               ORDER BY sc.ClaimType ASC";

    return legacyDbFetchAll($query)->toArray();
}

/**
 * Gets all the filtered claim list data.
 * This includes user, game information, claim type, set type, claim status and claim timestamps.
 *
 * Results are configurable based on input parameters, allowing sorting on each of the
 * above stats and returning data for a specific user or game.
 *
 * @return Collection<int, array>
 */
function getFilteredClaims(
    ?int $gameID = null,
    int $claimFilter = ClaimFilters::AllFilters,
    int $sortType = ClaimSorting::ClaimDateDescending,
    bool $getExpiringOnly = false,
    ?string $username = null,
    ?int $offset = null,
    ?int $limit = null
): Collection {
    $primaryClaim = ($claimFilter & ClaimFilters::PrimaryClaim);
    $collaborationClaim = ($claimFilter & ClaimFilters::CollaborationClaim);
    $newSetClaim = ($claimFilter & ClaimFilters::NewSetClaim);
    $revisionClaim = ($claimFilter & ClaimFilters::RevisionClaim);
    $specialNoneClaim = ($claimFilter & ClaimFilters::SpecialNone);
    $specialRevisionClaim = ($claimFilter & ClaimFilters::SpecialOwnRevision);
    $specialRolloutClaim = ($claimFilter & ClaimFilters::SpecialFreeRollout);
    $specialScheduledClaim = ($claimFilter & ClaimFilters::SpecialScheduledRelease);
    $developerClaim = ($claimFilter & ClaimFilters::DeveloperClaim);
    $juniorDeveloperClaim = ($claimFilter & ClaimFilters::JuniorDeveloperClaim);

    // Create claim type condition
    $claimTypeCondition = '';
    if ($primaryClaim && !$collaborationClaim) {
        $claimTypeCondition = 'AND sc.ClaimType = ' . ClaimType::Primary;
    } elseif (!$primaryClaim && $collaborationClaim) {
        $claimTypeCondition = 'AND sc.ClaimType = ' . ClaimType::Collaboration;
    } elseif (!$primaryClaim && !$collaborationClaim) {
        return collect();
    }

    // Create set type condition
    $setTypeCondition = '';
    if ($newSetClaim && !$revisionClaim) {
        $setTypeCondition = 'AND sc.SetType = ' . ClaimSetType::NewSet;
    } elseif (!$newSetClaim && $revisionClaim) {
        $setTypeCondition = 'AND sc.SetType = ' . ClaimSetType::Revision;
    } elseif (!$newSetClaim && !$revisionClaim) {
        return collect();
    }

    // Create the claim status condition
    $statuses = [];
    if ($claimFilter & ClaimFilters::ActiveClaim) {
        $statuses[] = ClaimStatus::Active;
    }
    if ($claimFilter & ClaimFilters::InReviewClaim) {
        $statuses[] = ClaimStatus::InReview;
    }
    if ($claimFilter & ClaimFilters::CompleteClaim) {
        $statuses[] = ClaimStatus::Complete;
    }
    if ($claimFilter & ClaimFilters::DroppedClaim) {
        $statuses[] = ClaimStatus::Dropped;
    }
    if (empty($statuses)) {
        return collect();
    }
    $statusCondition = '';
    if ($statuses != ClaimStatus::cases()) {
        $statusCondition = 'AND sc.Status IN (' . join(',', $statuses) . ')';
    }

    // Create the special condition
    $str = ($specialNoneClaim ? ClaimSpecial::None . ',' : '');
    $str .= ($specialRevisionClaim ? ClaimSpecial::OwnRevision . ',' : '');
    $str .= ($specialRolloutClaim ? ClaimSpecial::FreeRollout . ',' : '');
    $str .= ($specialScheduledClaim ? ClaimSpecial::ScheduledRelease : '');

    if (!(strlen($str) % 2)) { // Remove trailing comma if necessary
        $str = rtrim($str, ",");
    }

    $specialCondition = 'AND FALSE';
    if (strlen($str) > 0) {
        $specialCondition = "AND sc.Special IN ($str)";
    }

    // Create the developer status condition
    $devStatusCondition = '';
    if ($developerClaim && !$juniorDeveloperClaim) {
        $devStatusCondition = "AND ua.Permissions >= " . Permissions::Developer;
    } elseif (!$developerClaim && $juniorDeveloperClaim) {
        $devStatusCondition = "AND ua.Permissions = " . Permissions::JuniorDeveloper;
    } elseif (!$developerClaim && !$juniorDeveloperClaim) {
        $devStatusCondition = "AND ua.Permissions < " . Permissions::JuniorDeveloper;
    }

    // Determine ascending or descending order
    if ($sortType < 10) {
        $sortOrder = "DESC";
    } else {
        $sortOrder = "ASC";
        $sortType = $sortType - 10;
    }

    // Create the sorting condition
    $sortCondition = match ($sortType) {
        2 => 'sc.User ',
        3 => 'gd.Title ',
        4 => 'sc.ClaimType ',
        5 => 'sc.SetType ',
        6 => 'sc.Status ',
        7 => 'sc.Special ',
        8 => 'sc.Created ',
        9 => 'sc.Finished ',
        default => 'sc.Created ',
    };

    $sortCondition .= $sortOrder;

    $bindings = [];

    $userCondition = '';
    if (isset($username)) {
        $bindings['username'] = $username;
        $userCondition = "AND sc.User = :username";
    }

    $gameCondition = '';
    if ($gameID !== null && $gameID > 0) {
        $bindings['gameId'] = $gameID;
        $gameCondition = "AND sc.game_id = :gameId";
    }

    // Get expiring claims only
    $havingCondition = '';
    if ($getExpiringOnly) {
        $havingCondition = "HAVING MinutesLeft <= 10080"; // 7 days = 7 * 24 * 60
    }

    // Get either the filtered count or the filtered data
    $selectCondition = "
        sc.ID AS ID,
        sc.User AS User,
        sc.game_id AS GameID,
        gd.Title AS GameTitle,
        gd.ImageIcon AS GameIcon,
        c.ID AS ConsoleID,
        c.Name AS ConsoleName,
        sc.ClaimType AS ClaimType,
        sc.SetType AS SetType,
        sc.Status AS Status,
        sc.Extension AS Extension,
        sc.Special AS Special,
        sc.Created AS Created,
        sc.Finished AS DoneTime,
        sc.Updated AS Updated,
        CASE WHEN ua.Permissions <= 2 THEN true ELSE false END AS UserIsJrDev,
    ";
    $selectCondition .= diffMinutesRemainingStatement('sc.Finished', 'MinutesLeft');

    $query = "
        SELECT
            $selectCondition
        FROM
            SetClaim sc
        LEFT JOIN
            GameData AS gd ON gd.ID = sc.game_id
        LEFT JOIN
            Console AS c ON c.ID = gd.ConsoleID
        LEFT JOIN
            UserAccounts AS ua ON ua.User = sc.User
        WHERE
            TRUE
            $claimTypeCondition
            $setTypeCondition
            $statusCondition
            $specialCondition
            $devStatusCondition
            $userCondition
            $gameCondition
            $havingCondition
        ORDER BY
            $sortCondition";

    if ($limit !== null) {
        $query .= ' LIMIT';
        if ($offset !== null) {
            $query .= ' :offset,';
            $bindings['offset'] = $offset;
        }
        $query .= ' :limit';
        $bindings['limit'] = $limit;
    }

    return legacyDbFetchAll($query, $bindings);
}

/**
 * Gets the number of active claims the user currently has or the total amoung all users. Has the
 * option to count or ignore collaboration claims.
 */
function getActiveClaimCount(?string $user = null, bool $countCollaboration = true, bool $countSpecial = false): int
{
    $bindings = [];

    $userCondition = '';
    if (isset($user)) {
        $bindings['user'] = $user;
        $userCondition = "AND User = :user";
    }

    $claimTypeCondition = '';
    if (!$countCollaboration) {
        $bindings['type'] = ClaimType::Primary;
        $claimTypeCondition = 'AND ClaimType = :type';
    }

    $specialCondition = '';
    if (!$countSpecial) {
        $bindings['special'] = ClaimSpecial::None;
        $specialCondition = 'AND Special = :special';
    }

    $query = "
        SELECT
            COUNT(*) AS ActiveClaims
        FROM
            SetClaim
        WHERE
            TRUE
            $userCondition
            $claimTypeCondition
            $specialCondition
            AND Status IN (" . ClaimStatus::Active . ',' . ClaimStatus::InReview . ")";

    return (int) legacyDbFetch($query, $bindings)['ActiveClaims'];
}

/**
 * Updates a claim in the database. This function is only called when an admin updates a
 * claim from the Manage Claims page.
 */
function updateClaim(int $claimID, int $claimType, int $setType, int $status, int $special, string $claimDate, string $finishedDate): bool
{
    $claim = AchievementSetClaim::firstWhere('ID', $claimID);
    if (!$claim) {
        return false;
    }

    $oldFinishedDate = $claim->Finished;

    $claim->ClaimType = $claimType;
    $claim->SetType = $setType;
    $claim->Status = $status;
    $claim->Special = $special;
    $claim->Created = Carbon::parse($claimDate);
    $claim->Finished = Carbon::parse($finishedDate);
    $claim->save();

    if ($claim->Finished !== $oldFinishedDate) {
        $cacheKey = CacheKey::buildUserExpiringClaimsCacheKey($claim->User);
        Cache::forget($cacheKey);
    }

    return true;
}

/**
 * Gets the number of expiring and expired claims for a specific user.
 */
function getExpiringClaim(string $username): array
{
    if (empty($username)) {
        return [];
    }

    $cacheKey = CacheKey::buildUserExpiringClaimsCacheKey($username);

    $value = Cache::get($cacheKey);
    if ($value !== null) {
        return $value;
    }

    $claims = AchievementSetClaim::select(
        DB::raw('COALESCE(SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, NOW(), Finished) <= 0 THEN 1 ELSE 0 END), 0) AS Expired'),
        DB::raw('COALESCE(SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, NOW(), Finished) BETWEEN 0 AND 10080 THEN 1 ELSE 0 END), 0) AS Expiring'),
        DB::raw('COUNT(*) AS Count')
    )
        ->where('User', $username)
        ->whereIn('Status', [ClaimStatus::Active, ClaimStatus::InReview])
        ->where('Special', '!=', ClaimSpecial::ScheduledRelease)
        ->first();

    if (!$claims || $claims['Count'] == 0) {
        $value = [];
        // new claim expiration is 30 days and expiration warning is 7 days, so this guarantees a refresh before expiration
        Cache::put($cacheKey, $value, Carbon::now()->addDays(20));
    } else {
        $value = [
            'Expired' => $claims->Expired,
            'Expiring' => $claims->Expiring,
        ];
        // refresh once an hour. this query only takes about 2ms, so it's not super expensive, but
        // we want to avoid doing it on every page load.
        Cache::put($cacheKey, $value, Carbon::now()->addHours(1));
    }

    return $value;
}

/**
 * Gets the number of claims a user is allowed to have based on their permission
 */
function permissionsToClaim(int $permissions): int
{
    return match ($permissions) {
        Permissions::Spam => 0,
        Permissions::Banned => 0,
        Permissions::Unregistered => 0,
        Permissions::Registered => 0,
        Permissions::JuniorDeveloper => 1,
        Permissions::Developer => 4,
        Permissions::Moderator => 4,
        default => 0,
    };
}
