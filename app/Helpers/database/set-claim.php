<?php

use App\Community\Enums\ClaimFilters;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSorting;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Community\Models\AchievementSetClaim;
use App\Site\Enums\Permissions;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Checks if the user is able to make a claim and inserts the claim into the database. If the claim
 * is a collaboration claim then the claim will have the same Finished time as the primary claim for the game.
 */
function insertClaim(string $user, int $gameID, int $claimType, int $setType, int $special, int $permissions): bool
{
    if ($claimType === ClaimType::Primary) {
        // Primary Claim
        // Prevent if user has no available slots except for when they are the sole dev of the set
        if ($special === ClaimSpecial::None && getActiveClaimCount($user, false) >= permissionsToClaim($permissions)) {
            return false;
        }

        $now = Carbon::now();
        $finished = Carbon::now()->addMonths(3);

        $query = "
            INSERT INTO
                SetClaim (`User`, `GameID`, `ClaimType`, `SetType`, `Status`, `Extension`, `Special`, `Created`, `Finished` ,`Updated`)
            VALUES
                ('$user', '$gameID', '$claimType', '$setType', '" . ClaimStatus::Active . "', '0', '$special', '$now', '$finished', '$now')";

        return legacyDbStatement($query);
    }

    // Collaboration claim
    // For a collaboration claim we want to use the same Finished time as the primary claim
    $query = "
        INSERT INTO
            SetClaim (`User`, `GameID`, `ClaimType`, `SetType`, `Status`, `Extension`, `Special`, `Created`, `Finished` ,`Updated`)
        VALUES
            ('$user', '$gameID', '$claimType', '$setType', '" . ClaimStatus::Active . "', '0', '" . ClaimSpecial::None . "', NOW(),
            (SELECT Finished FROM (SELECT Finished FROM SetClaim WHERE GameID = '$gameID' AND Status IN (" . ClaimStatus::Active . ',' . ClaimStatus::InReview . ") AND ClaimType = " . ClaimType::Primary . ") AS sc),
            NOW())";

    return legacyDbStatement($query);
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
            AND GameID = :gameId";

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
                AND GameID = '$gameID'";

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
            AND GameID = '$gameID'";

    return legacyDbStatement($query);
}

/**
 * Extends a claim a months beyone it's initial expiration time if it expires withing a week.
 * Any collaboration claims will be extended as well.
 */
function extendClaim(string $user, int $gameID): bool
{
    sanitize_sql_inputs($gameID);

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
                AND GameID = '$gameID'
                AND TIMESTAMPDIFF(MINUTE, NOW(), Finished) <= 10080"; // 7 days = 7 * 24 * 60

        if (s_mysql_query($query)) {
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
    $query = "SELECT sc.User as User, sc.SetType as SetType, sc.GameID as GameID,
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

    $query .= " FROM SetClaim sc WHERE sc.GameID IN ($gameIDs)
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
        $gameCondition = "AND sc.GameID = :gameId";
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
        sc.GameID AS GameID,
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
            GameData AS gd ON gd.ID = sc.GameID
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
    sanitize_sql_inputs($claimID, $claimType, $setType, $status, $special, $claimDate, $finishedDate);

    $query = "
        UPDATE
            SetClaim
        SET
            ClaimType = '$claimType',
            SetType = '$setType',
            Status = '$status',
            Special = '$special',
            Created = '$claimDate',
            Finished = '$finishedDate',
            Updated = NOW()
        WHERE
            ID = '$claimID'";

    return (bool) s_mysql_query($query);
}

/**
 * Gets the number of expiring and expired claims for a specific user.
 */
function getExpiringClaim(string $username): array
{
    if (empty($username)) {
        return [];
    }

    $claims = AchievementSetClaim::select(
        DB::raw('COALESCE(SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, NOW(), Finished) <= 0 THEN 1 ELSE 0 END), 0) AS Expired'),
        DB::raw('COALESCE(SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, NOW(), Finished) BETWEEN 0 AND 10080 THEN 1 ELSE 0 END), 0) AS Expiring')
    )
        ->where('User', $username)
        ->whereIn('Status', [ClaimStatus::Active, ClaimStatus::InReview])
        ->where('Special', '!=', ClaimSpecial::ScheduledRelease)
        ->first();

    if (!$claims) {
        return [];
    }

    return [
        'Expired' => $claims->Expired,
        'Expiring' => $claims->Expiring,
    ];
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
