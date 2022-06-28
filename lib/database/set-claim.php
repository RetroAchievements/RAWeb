<?php

use RA\ClaimFilters;
use RA\ClaimSetType;
use RA\ClaimSorting;
use RA\ClaimSpecial;
use RA\ClaimStatus;
use RA\ClaimType;
use RA\Permissions;

/**
 * Checks if the user is able to make a claim and inserts the claim into the database. If the claim
 * is a collaboration claim then the claim will have the same Finished time as the primary claim for the game.
 */
function insertClaim(string $user, int $gameID, int $claimType, int $setType, int $special, int $permissions): bool
{
    sanitize_sql_inputs($user, $gameID, $claimType, $setType, $special);

    if ($claimType == ClaimType::Primary) { // Primary Claim
        // Prevent if user has no available slots except for when they are the sole dev of the set
        if ($special == ClaimSpecial::None && getActiveClaimCount($user, false) >= permissionsToClaim($permissions)) {
            return false;
        }

        $query = "
            INSERT INTO
                SetClaim (`User`, `GameID`, `ClaimType`, `SetType`, `Status`, `Extension`, `Special`, `Created`, `Finished` ,`Updated`)
            VALUES
                ('$user', '$gameID', '$claimType', '$setType', '" . ClaimStatus::Active . "', '0', '$special', NOW(), DATE_ADD(NOW(), INTERVAL 3 MONTH), NOW())";

        if (s_mysql_query($query)) {
            return true;
        }
    } else { // Collaboration claim
        // For a collaboration claim we want to use the same Finished time as the primary claim
        $query = "
            INSERT INTO
                SetClaim (`User`, `GameID`, `ClaimType`, `SetType`, `Status`, `Extension`, `Special`, `Created`, `Finished` ,`Updated`)
            VALUES
                ('$user', '$gameID', '$claimType', '$setType', '" . ClaimStatus::Active . "', '0', '" . ClaimSpecial::None . "', NOW(),
                (SELECT Finished FROM (SELECT Finished FROM SetClaim WHERE GameID = '$gameID' AND Status = " . ClaimStatus::Active . " AND ClaimType = " . ClaimType::Primary . ") AS sc),
                NOW())";

        if (s_mysql_query($query)) {
            return true;
        }
    }
    return false;
}

/**
 * Checks if the user already has the game claimed. Allows for cheicng primary and collaboration claims.
 */
function hasSetClaimed(string $user, int $gameID, bool $isPrimaryClaim = false): bool
{
    sanitize_sql_inputs($user, $gameID);

    $claimTypeCondition = '';
    if ($isPrimaryClaim) {
        $claimTypeCondition = 'AND ClaimType = ' . ClaimType::Primary;
    }

    $query = "
        SELECT
            COUNT(*) AS claimCount
        FROM
            SetClaim
        WHERE
            Status = " . ClaimStatus::Active . "
            $claimTypeCondition
            AND User = '$user'
            AND GameID = '$gameID'";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        if (mysqli_fetch_assoc($dbResult)['claimCount'] > 0) {
            return true;
        }
    }

    return false;
}

/**
 * Marks a claim as complete after verifying that the user completing the claim
 * has the primary claim on the game. Any collaboration claims will also be
 * marked as complete.
 */
function completeClaim(string $user, int $gameID): bool
{
    sanitize_sql_inputs($gameID);

    // Only allow primary claim user to mark a claim as complete
    if (hasSetClaimed($user, $gameID, true)) {
        $query = "
            UPDATE
                SetClaim
            SET
                Status = " . ClaimStatus::Complete . ",
                Finished = NOW(),
                Updated = NOW()
            WHERE
                Status = " . ClaimStatus::Active . "
                AND GameID = '$gameID'";

        if (s_mysql_query($query)) {
            return true;
        }
    }
    return false;
}

/**
 * Marks a claim as dropped.
 */
function dropClaim(string $user, int $gameID): bool
{
    sanitize_sql_inputs($user, $gameID);

    $query = "
        UPDATE
            SetClaim
        SET
            Status =  " . ClaimStatus::Dropped . ",
            Finished = NOW(),
            Updated = NOW()
        WHERE
            Status = " . ClaimStatus::Active . "
            AND User = '$user'
            AND GameID = '$gameID'";

    if (s_mysql_query($query)) {
        return true;
    }
    return false;
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
                Status = " . ClaimStatus::Active . "
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
function getClaimData(int $gameID, bool $getFullData = true): array
{
    sanitize_sql_inputs($gameID);

    $retVal = [];
    if ($getFullData) {
        $query = "
        SELECT
            sc.User as User,
            sc.SetType as SetType,
            sc.ClaimType as ClaimType,
            sc.Created as Created,
            sc.Finished as Expiration,
            sc.Status as Status,
            TIMESTAMPDIFF(MINUTE, NOW(), sc.Finished) AS MinutesLeft,
            TIMESTAMPDIFF(MINUTE, sc.Created, NOW()) AS MinutesActive";
    } else {
        $query = "
        SELECT
            sc.User as User,
            sc.SetType as SetType,
            sc.ClaimType as ClaimType,
            sc.Created as Created,
            sc.Finished as Expiration";
    }
    $query .= "
        FROM
            SetClaim sc
        WHERE
            sc.GameID = '$gameID'
            AND sc.Status = " . ClaimStatus::Active . "
        ORDER BY
            sc.ClaimType ASC";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $nextData;
        }
    }
    return $retVal;
}

/**
 * Gets all the filtered claim list data.
 * This includes user, game information, claim type, set type, claim status and claim timestamps.
 *
 * Results are configurable based on input parameters, allowing sorting on each of the
 * above stats and returning data for a specific user or game.
 */
function getFilteredClaimData(int $gameID = 0, int $claimFilter = ClaimFilters::AllFilters, int $sortType = ClaimSorting::ClaimDateDescending, bool $getExpiringOnly = false, ?string $username = null, bool $getCount = false, int $offset = 0, int $limit = 50): array|int
{
    $retVal = [];
    sanitize_sql_inputs($gameID, $username);

    $primaryClaim = ($claimFilter & ClaimFilters::PrimaryClaim);
    $collaborationClaim = ($claimFilter & ClaimFilters::CollaborationClaim);
    $newSetClaim = ($claimFilter & ClaimFilters::NewSetClaim);
    $revisionClaim = ($claimFilter & ClaimFilters::RevisionClaim);
    $activeClaim = ($claimFilter & ClaimFilters::ActiveClaim);
    $completeClaim = ($claimFilter & ClaimFilters::CompleteClaim);
    $droppedClaim = ($claimFilter & ClaimFilters::DroppedClaim);
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
        if ($getCount) {
            return 0;
        }
        return $retVal;
    }

    // Create set type condition
    $setTypeCondition = '';
    if ($newSetClaim && !$revisionClaim) {
        $setTypeCondition = 'AND sc.SetType = ' . ClaimSetType::NewSet;
    } elseif (!$newSetClaim && $revisionClaim) {
        $setTypeCondition = 'AND sc.SetType = ' . ClaimSetType::Revision;
    } elseif (!$newSetClaim && !$revisionClaim) {
        if ($getCount) {
            return 0;
        }
        return $retVal;
    }

    // Create the claim status condition
    $statusCondition = '';
    if ($activeClaim && $completeClaim && !$droppedClaim) {
        $statusCondition = 'AND sc.Status IN (' . ClaimStatus::Active . ', ' . ClaimStatus::Complete . ')';
    } elseif ($activeClaim && !$completeClaim && $droppedClaim) {
        $statusCondition = 'AND sc.Status IN (' . ClaimStatus::Active . ', ' . ClaimStatus::Dropped . ')';
    } elseif ($activeClaim && !$completeClaim && !$droppedClaim) {
        $statusCondition = 'AND sc.Status = ' . ClaimStatus::Active;
    } elseif (!$activeClaim && $completeClaim && $droppedClaim) {
        $statusCondition = 'AND sc.Status IN (' . ClaimStatus::Complete . ', ' . ClaimStatus::Dropped . ')';
    } elseif (!$activeClaim && $completeClaim && !$droppedClaim) {
        $statusCondition = 'AND sc.Status = ' . ClaimStatus::Complete;
    } elseif (!$activeClaim && !$completeClaim && $droppedClaim) {
        $statusCondition = 'AND sc.Status = ' . ClaimStatus::Dropped;
    } elseif (!$activeClaim && !$completeClaim && !$droppedClaim) {
        if ($getCount) {
            return 0;
        }
        return $retVal;
    }

    // Create the special condition
    $str = '';
    $str .= ($specialNoneClaim ? ClaimSpecial::None . ',' : '');
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
    }
    . $sortOrder;

    // Creare the user data condition
    $userCondition = '';
    if (isset($username)) {
        $userCondition = "AND sc.User = '$username'";
    }

    // Create the game condition
    $gameCondition = '';
    if ($gameID > 0) {
        $gameCondition = "AND sc.GameID = '$gameID'";
    }

    // Get expiring claims only
    $havingCondition = '';
    if ($getExpiringOnly) {
        $havingCondition = "HAVING MinutesLeft <= 10080"; // 7 days = 7 * 24 * 60
    }

    // Get either the filtered count or the filtered data
    if ($getCount) {
        $selectCondition = "COUNT(*) AS Total";
    } else {
        $selectCondition =
        "sc.ID AS ID,
        sc.User AS User,
        sc.GameID AS GameID,
        gd.Title AS GameTitle,
        gd.ImageIcon AS GameIcon,
        c.Name AS ConsoleName,
        sc.ClaimType AS ClaimType,
        sc.SetType AS SetType,
        sc.Status AS Status,
        sc.Extension AS Extension,
        sc.Special AS Special,
        sc.Created AS Created,
        sc.Finished AS DoneTime,
        sc.Updated AS Updated,
        TIMESTAMPDIFF(MINUTE, NOW(), sc.Finished) AS MinutesLeft";
    }

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
            $sortCondition
        LIMIT
            $offset, $limit";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        if ($getCount) {
            return (int) (mysqli_fetch_assoc($dbResult)['Total'] ?? 0);
        }
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $nextData;
        }
    }

    return $retVal;
}

/**
 * Gets the number of active claims the user currently has or the total amoung all users. Has the
 * option to count or ignore collaboration claims.
 */
function getActiveClaimCount(?string $user = null, bool $countCollaboration = true, bool $countSpecial = false): int
{
    $userCondition = '';
    if (isset($user)) {
        sanitize_sql_inputs($user);
        $userCondition = "AND User = '$user'";
    }

    $claimTypeCondition = '';
    if (!$countCollaboration) {
        $claimTypeCondition = 'AND ClaimType = ' . ClaimType::Primary;
    }

    $specialCondition = '';
    if (!$countSpecial) {
        $specialCondition = 'AND Special = ' . ClaimSpecial::None;
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
            AND Status = " . ClaimStatus::Active;

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        return (int) (mysqli_fetch_assoc($dbResult)['ActiveClaims'] ?? 0);
    }
    return 0;
}

/**
 * Updates a claim in the database. This function is only called when an admin updates a
 * claim from the Manage Claims page.
 */
function updateClaim(int $claimID, int $claimType, int $setType, int $status, string $special, string $claimDate, string $finishedDate): bool
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

    if (s_mysql_query($query)) {
        return true;
    }
    return false;
}

/**
 * Gets the number of expiring and expired claims for a  specific user.
 */
function getExpiringClaim(string $user): array
{
    $retVal = [];
    sanitize_sql_inputs($user);

    $query = "
        SELECT
            COALESCE(SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, NOW(), sc.Finished) <= 0 THEN 1 ELSE 0 END), 0) AS Expired,
            COALESCE(SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, NOW(), sc.Finished) BETWEEN 0 AND 10080 THEN 1 ELSE 0 END), 0) AS Expiring
        FROM
            SetClaim AS sc
        WHERE
            sc.User = '$user'
            AND sc.Status = " . ClaimStatus::Active . "
            AND sc.Special != " . ClaimSpecial::ScheduledRelease;

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult);
    }
    return $retVal;
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
        Permissions::Admin => 4,
        default => 0,
    };
}
