<?php

use App\Community\Enums\ClaimFilters;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSorting;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Community\Enums\CommentableType;
use App\Enums\Permissions;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\User;
use App\Support\Cache\CacheKey;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Checks if the user already has the game claimed. Allows for checking primary/collaboration claims as well as set type.
 */
function hasSetClaimed(User $user, int $gameId, bool $isPrimaryClaim = false, ?ClaimSetType $setType = null): bool
{
    $query = AchievementSetClaim::where('user_id', $user->id)
        ->where('game_id', $gameId)
        ->whereIn('status', [ClaimStatus::Active, ClaimStatus::InReview]);

    if ($isPrimaryClaim) {
        $query->primaryClaim();
    }

    if ($setType !== null) {
        $query->setType($setType);
    }

    return $query->exists();
}

function updateClaimsForPermissionChange(User $user, int $permissionsAfter, int $permissionsBefore, ?string $actingUsername = null): void
{
    // Junior developers can have claims in review.
    // When being promoted from Junior Developer, change any In Review claims to Active.
    if ($permissionsBefore === Permissions::JuniorDeveloper && $permissionsAfter > Permissions::JuniorDeveloper) {
        $permissionsString = Permissions::toString($permissionsAfter);
        if (!empty($actingUsername)) {
            $comment = "{$actingUsername} updated {$user->display_name}'s claim via promotion to {$permissionsString}.";
        } else {
            $comment = "{$user->display_name}'s claim updated via promotion to {$permissionsString}.";
        }
        $comment .= " Claim Status: " . ClaimStatus::Active->label();

        $inReviewClaims = $user->achievementSetClaims()
            ->where('status', ClaimStatus::InReview)->get();
        foreach ($inReviewClaims as $claim) {
            $claim->status = ClaimStatus::Active;
            $claim->save();

            addArticleComment('Server', CommentableType::SetClaim, $claim->game_id, $comment);
        }
    }

    // If the user loses developer permissions, drop all claims held by the user.
    if ($permissionsBefore >= Permissions::JuniorDeveloper && $permissionsAfter < Permissions::JuniorDeveloper) {
        $permissionsString = Permissions::toString($permissionsAfter);

        $activeClaims = $user->achievementSetClaims()
            ->whereIn('status', [ClaimStatus::Active, ClaimStatus::InReview])->get();
        foreach ($activeClaims as $claim) {
            $claim->status = ClaimStatus::Dropped;
            $claim->save();

            if (!empty($actingUsername)) {
                $comment = "{$actingUsername} dropped {$user->display_name}'s " . $claim->claim_type->label() . " claim via demotion to {$permissionsString}.";
            } else {
                $comment = "{$user->display_name}'s " . $claim->claim_type->label() . " claim dropped via demotion to {$permissionsString}.";
            }

            addArticleComment('Server', CommentableType::SetClaim, $claim->game_id, $comment);
        }
    }
}

/**
 * Gets the claim data for a specific game to display to the users.
 */
function getClaimData(array $gameIds, bool $getFullData = true): array
{
    if (empty($gameIds)) {
        return [];
    }

    $selectColumns = [
        'user_id',
        'set_type as SetType',
        'game_id as GameID',
        'claim_type as ClaimType',
        'created_at as Created',
        'finished_at as Expiration',
    ];

    if ($getFullData) {
        $selectColumns = array_merge($selectColumns, [
            'status as Status',
            'id as ID',
            DB::raw(diffMinutesRemainingStatement('finished_at', 'MinutesLeft')),
            DB::raw(diffMinutesPassedStatement('created_at', 'MinutesActive')),
        ]);
    }

    $claims = AchievementSetClaim::whereIn('game_id', $gameIds)
        ->whereIn('status', [ClaimStatus::Active, ClaimStatus::InReview])
        ->select($selectColumns)
        ->get()
        ->toArray();

    // Fetch the usernames and stitch them into the result.
    $userIds = array_column($claims, 'user_id');
    $usernames = User::whereIn('id', array_unique($userIds))->pluck('username', 'id')->toArray();

    return array_map(function ($claim) use ($usernames) {
        $claim['User'] = $usernames[$claim['user_id']] ?? 'Deleted User';
        unset($claim['user_id']);

        return $claim;
    }, $claims);
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
    ?int $limit = null,
    bool $useLegacyIntegers = false,
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

    // Create claim type condition.
    $claimTypeCondition = '';
    if ($primaryClaim && !$collaborationClaim) {
        $claimTypeCondition = "AND sc.claim_type = '" . ClaimType::Primary->value . "'";
    } elseif (!$primaryClaim && $collaborationClaim) {
        $claimTypeCondition = "AND sc.claim_type = '" . ClaimType::Collaboration->value . "'";
    } elseif (!$primaryClaim && !$collaborationClaim) {
        return collect();
    }

    // Create set type condition.
    $setTypeCondition = '';
    if ($newSetClaim && !$revisionClaim) {
        $setTypeCondition = "AND sc.set_type = '" . ClaimSetType::NewSet->value . "'";
    } elseif (!$newSetClaim && $revisionClaim) {
        $setTypeCondition = "AND sc.set_type = '" . ClaimSetType::Revision->value . "'";
    } elseif (!$newSetClaim && !$revisionClaim) {
        return collect();
    }

    // Create the claim status condition.
    $statuses = [];
    if ($claimFilter & ClaimFilters::ActiveClaim) {
        $statuses[] = "'" . ClaimStatus::Active->value . "'";
    }
    if ($claimFilter & ClaimFilters::InReviewClaim) {
        $statuses[] = "'" . ClaimStatus::InReview->value . "'";
    }
    if ($claimFilter & ClaimFilters::CompleteClaim) {
        $statuses[] = "'" . ClaimStatus::Complete->value . "'";
    }
    if ($claimFilter & ClaimFilters::DroppedClaim) {
        $statuses[] = "'" . ClaimStatus::Dropped->value . "'";
    }
    if (empty($statuses)) {
        return collect();
    }
    $statusCondition = '';
    $allStatusValues = array_map(fn ($case) => "'" . $case->value . "'", ClaimStatus::cases());
    if ($statuses != $allStatusValues) {
        $statusCondition = 'AND sc.status IN (' . join(',', $statuses) . ')';
    }

    // Create the special condition.
    $specials = [];
    if ($specialNoneClaim) {
        $specials[] = "'" . ClaimSpecial::None->value . "'";
    }
    if ($specialRevisionClaim) {
        $specials[] = "'" . ClaimSpecial::OwnRevision->value . "'";
    }
    if ($specialRolloutClaim) {
        $specials[] = "'" . ClaimSpecial::FreeRollout->value . "'";
    }
    if ($specialScheduledClaim) {
        $specials[] = "'" . ClaimSpecial::ScheduledRelease->value . "'";
    }

    $specialCondition = 'AND FALSE';
    if (!empty($specials)) {
        $specialCondition = "AND sc.special_type IN (" . join(',', $specials) . ")";
    }

    // Create the developer status condition.
    $devStatusCondition = '';
    if ($developerClaim && !$juniorDeveloperClaim) {
        $devStatusCondition = "AND ua.Permissions >= " . Permissions::Developer;
    } elseif (!$developerClaim && $juniorDeveloperClaim) {
        $devStatusCondition = "AND ua.Permissions = " . Permissions::JuniorDeveloper;
    } elseif (!$developerClaim && !$juniorDeveloperClaim) {
        $devStatusCondition = "AND ua.Permissions < " . Permissions::JuniorDeveloper;
    }

    // Determine ascending or descending order.
    if ($sortType < 10) {
        $sortOrder = "DESC";
    } else {
        $sortOrder = "ASC";
        $sortType = $sortType - 10;
    }

    // Create the sorting condition.
    $sortCondition = match ($sortType) {
        2 => 'ua.username ',
        3 => 'gd.title ',
        4 => 'sc.claim_type ',
        5 => 'sc.set_type ',
        6 => 'sc.status ',
        7 => 'sc.special_type ',
        8 => 'sc.created_at ',
        9 => 'sc.finished_at ',
        default => 'sc.created_at ',
    };

    $sortCondition .= $sortOrder;

    $bindings = [];

    $userCondition = '';
    if (isset($username)) {
        $bindings['username'] = $username;
        $bindings['display_name'] = $username;
        $userCondition = "AND (ua.username = :username OR ua.display_name = :display_name)";
    }

    $gameCondition = '';
    if ($gameID !== null && $gameID > 0) {
        $bindings['gameId'] = $gameID;
        $gameCondition = "AND sc.game_id = :gameId";
    }

    // Get expiring claims only.
    $havingCondition = '';
    if ($getExpiringOnly) {
        $havingCondition = "HAVING MinutesLeft <= 10080"; // 7 days = 7 * 24 * 60
    }

    // Get either the filtered count or the filtered data.
    $selectCondition = "
        sc.id AS ID,
        ua.ulid as ULID,
        COALESCE(ua.display_name, ua.username) AS User,
        sc.game_id AS GameID,
        gd.title AS GameTitle,
        gd.image_icon_asset_path AS GameIcon,
        s.id AS ConsoleID,
        s.name AS ConsoleName,
        sc.claim_type AS ClaimType,
        sc.set_type AS SetType,
        sc.status AS Status,
        sc.extensions_count AS Extension,
        sc.special_type AS Special,
        sc.created_at AS Created,
        sc.finished_at AS DoneTime,
        sc.updated_at AS Updated,
        CASE WHEN ua.Permissions <= 2 THEN true ELSE false END AS UserIsJrDev,
    ";
    $selectCondition .= diffMinutesRemainingStatement('sc.finished_at', 'MinutesLeft');

    $query = "
        SELECT
            $selectCondition
        FROM
            achievement_set_claims sc
        LEFT JOIN
            games AS gd ON gd.id = sc.game_id
        LEFT JOIN
            systems AS s ON s.id = gd.system_id
        LEFT JOIN
            users AS ua ON ua.id = sc.user_id
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

    $results = legacyDbFetchAll($query, $bindings);

    // For V1 API backward compatibility, convert string enum values to legacy integers.
    if ($useLegacyIntegers) {
        $results = $results->map(function ($claim) {
            $claim['ClaimType'] = ClaimType::from($claim['ClaimType'])->toLegacyInteger();
            $claim['SetType'] = ClaimSetType::from($claim['SetType'])->toLegacyInteger();
            $claim['Status'] = ClaimStatus::from($claim['Status'])->toLegacyInteger();
            $claim['Special'] = ClaimSpecial::from($claim['Special'])->toLegacyInteger();

            return $claim;
        });
    }

    return $results;
}

/**
 * Gets the number of active claims the user currently has or the total among all users. Has the
 * option to count or ignore collaboration claims.
 */
function getActiveClaimCount(?User $user = null, bool $countCollaboration = true, bool $countSpecial = false): int
{
    $bindings = [];

    $userCondition = '';
    if (isset($user)) {
        $bindings['userId'] = $user->id;
        $userCondition = "AND user_id = :userId";
    }

    $claimTypeCondition = '';
    if (!$countCollaboration) {
        $bindings['type'] = ClaimType::Primary->value;
        $claimTypeCondition = 'AND claim_type = :type';
    }

    $specialCondition = '';
    if (!$countSpecial) {
        $bindings['special'] = ClaimSpecial::None->value;
        $specialCondition = 'AND special_type = :special';
    }

    $query = "
        SELECT
            COUNT(*) AS ActiveClaims
        FROM
            achievement_set_claims
        WHERE
            TRUE
            $userCondition
            $claimTypeCondition
            $specialCondition
            AND status IN ('" . ClaimStatus::Active->value . "','" . ClaimStatus::InReview->value . "')";

    return (int) legacyDbFetch($query, $bindings)['ActiveClaims'];
}

/**
 * Gets the number of expiring and expired claims for a specific user.
 */
function getExpiringClaim(User $user): array
{
    $cacheKey = CacheKey::buildUserExpiringClaimsCacheKey($user->username);

    $value = Cache::get($cacheKey);
    if ($value !== null) {
        return $value;
    }

    // TIMESTAMPDIFF() is not supported by SQLite, so any test that passes through
    // here will always fail. We need to add a special exception for when this is
    // called by tests.
    if (DB::connection()->getDriverName() === 'sqlite') {
        $minuteDiff = "round((julianday(finished_at) - julianday('now')) * 1440)";
    } else {
        $minuteDiff = "TIMESTAMPDIFF(MINUTE, NOW(), finished_at)";
    }

    $claims = AchievementSetClaim::select(
        DB::raw("COALESCE(SUM(CASE WHEN {$minuteDiff} <= 0 THEN 1 ELSE 0 END), 0) AS Expired"),
        DB::raw("COALESCE(SUM(CASE WHEN {$minuteDiff} BETWEEN 0 AND 10080 THEN 1 ELSE 0 END), 0) AS Expiring"),
        DB::raw('COUNT(*) AS Count')
    )
        ->where('user_id', $user->id)
        ->whereIn('status', [ClaimStatus::Active, ClaimStatus::InReview])
        ->where('special_type', '!=', ClaimSpecial::ScheduledRelease)
        ->first();

    if (!$claims || $claims['Count'] == 0) {
        $value = [];
        // New claim expiration is 30 days and expiration warning is 7 days, so this guarantees a refresh before expiration.
        Cache::put($cacheKey, $value, Carbon::now()->addDays(20));
    } else {
        $value = [
            'Expired' => $claims->Expired,
            'Expiring' => $claims->Expiring,
        ];
        // Refresh once an hour. This query only takes about 2ms, so it's not super expensive, but
        // we want to avoid doing it on every page load.
        Cache::put($cacheKey, $value, Carbon::now()->addHours(1));
    }

    return $value;
}
