<?php

use App\Community\Enums\ClaimStatus;
use App\Community\Enums\RequestStatus;
use App\Community\Enums\UserGameListType;
use App\Community\Models\UserGameListEntry;
use App\Platform\Enums\AchievementFlag;
use App\Site\Models\User;

/**
 * Gets a list of set requests made by a given user.
 */
function getUserRequestList(?string $user = null): array
{
    sanitize_sql_inputs($user);

    $retVal = [];

    $query = "
        SELECT
            sr.GameID as GameID,
            gd.Title as GameTitle,
            gd.ImageIcon as GameIcon,
            c.name as ConsoleName,
            GROUP_CONCAT(DISTINCT(IF(sc.Status = " . ClaimStatus::Active . ", sc.User, NULL))) AS Claims
        FROM
            SetRequest sr
        LEFT JOIN
            SetClaim sc ON (sr.GameID = sc.GameID)
        LEFT JOIN
            GameData gd ON (sr.GameID = gd.ID)
        LEFT JOIN
            Console c ON (gd.ConsoleID = c.ID)
        WHERE
            sr.user = '$user' AND sr.type='" . UserGameListType::AchievementSetRequest . "'
        GROUP BY
            sr.GameID
        ORDER BY
            GameTitle ASC";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $gameIDs = [];
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $gameIDs[] = $nextData['GameID'];
            $nextData['AchievementCount'] = 0;
            $retVal[] = $nextData;
        }

        if (!empty($gameIDs)) {
            $query = "SELECT GameID, COUNT(ID) AS AchievementCount FROM Achievements"
                   . " WHERE GameID IN (" . implode(',', $gameIDs) . ")"
                   . " AND Flags = " . AchievementFlag::OfficialCore
                   . " GROUP BY GameID";

            $dbResult = s_mysql_query($query);

            if ($dbResult !== false) {
                while ($nextData = mysqli_fetch_assoc($dbResult)) {
                    foreach ($retVal as &$game) {
                        if ($game['GameID'] == $nextData['GameID']) {
                            $game['AchievementCount'] = $nextData['AchievementCount'];
                            break;
                        }
                    }
                }
            } else {
                log_sql_fail();
            }
        }
    } else {
        log_sql_fail();
    }

    return $retVal;
}

/**
 * Gets the total and remaining set requests left for the given user.
 */
function getUserRequestsInformation(string $user, array $list, int $gameID = -1): array
{
    /** @var User $userModel */
    $userModel = User::firstWhere('User', $user);
    $requests = UserGameListEntry::getUserSetRequestsInformation($userModel);

    $requests['used'] = 0;
    $requests['requestedThisGame'] = 0;

    // Determine how many of the users current requests are still valid.
    // Requests made for games that since received achievements do not count towards a used request
    foreach ($list as $request) {
        // If the game does not have achievements then it counts as a legit request
        if ($request['AchievementCount'] == 0) {
            $requests['used']++;
        }

        // Determine if we have made a request for the input game
        if ($request['GameID'] == $gameID) {
            $requests['requestedThisGame'] = 1;
        }
    }

    $requests['remaining'] = $requests['total'] - $requests['used'];

    return $requests;
}

/**
 * Gets the number of set requests for a given game.
 */
function getSetRequestCount(int $gameID): int
{
    sanitize_sql_inputs($gameID);
    if ($gameID < 1) {
        return 0;
    }

    $query = "SELECT COUNT(*) AS Request FROM
                SetRequest
                WHERE GameID = $gameID
                AND type='" . UserGameListType::AchievementSetRequest . "'";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        return (int) (mysqli_fetch_assoc($dbResult)['Request'] ?? 0);
    }

    return 0;
}

/**
 * Gets a list of set requestors for a given game.
 */
function getSetRequestorsList(int $gameID, bool $getEmailInfo = false): array
{
    sanitize_sql_inputs($gameID);

    $retVal = [];

    if ($gameID < 1) {
        return [];
    }

    if ($getEmailInfo) {
        $query = "
        SELECT
            sr.User AS Requestor,
            ua.EmailAddress AS Email,
            gd.Title AS Title
        FROM
            SetRequest AS sr
        LEFT JOIN
            UserAccounts ua on ua.User = sr.User
        LEFT JOIN
            GameData gd ON sr.GameID = gd.ID
        WHERE
        GameID = $gameID
        AND sr.type='" . UserGameListType::AchievementSetRequest . "'";
    } else {
        $query = "
            SELECT
            User AS Requestor
        FROM
            SetRequest
        WHERE
            GameID = $gameID";
    }

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $nextData;
        }
    } else {
        log_sql_fail();
    }

    return $retVal;
}

/**
 * Gets a list of the most requested sets without core achievements.
 */
function getMostRequestedSetsList(array|int|null $console, int $offset, int $count, int $requestStatus = RequestStatus::Any): array
{
    sanitize_sql_inputs($offset, $count);

    $retVal = [];

    $query = "
        SELECT
            COUNT(DISTINCT(sr.User)) AS Requests,
            sr.GameID as GameID,
            gd.Title as GameTitle,
            gd.ImageIcon as GameIcon,
            c.name as ConsoleName,
            GROUP_CONCAT(DISTINCT(IF(sc.Status = " . ClaimStatus::Active . ", sc.User, NULL))) AS Claims
        FROM
            SetRequest sr
        LEFT JOIN
            SetClaim sc ON (sr.GameID = sc.GameID AND sc.Status = " . ClaimStatus::Active . ")
        LEFT JOIN
            GameData gd ON (sr.GameID = gd.ID)
        LEFT JOIN
            Console c ON (gd.ConsoleID = c.ID)
        WHERE
            sr.GameID NOT IN (SELECT DISTINCT(GameID) FROM Achievements where Flags = '3')
            AND sr.type='" . UserGameListType::AchievementSetRequest . "'";

    if (is_array($console)) {
        $query .= ' AND c.ID IN (' . implode(',', $console) . ') ';
    } elseif (!empty($console)) {
        sanitize_sql_inputs($console);
        $query .= " AND c.ID = $console ";
    }

    if ($requestStatus === RequestStatus::Claimed) {
        $query .= " AND sc.ID IS NOT NULL ";
    } elseif ($requestStatus === RequestStatus::Unclaimed) {
        $query .= " AND sc.ID IS NULL ";
    }

    $query .= "
            GROUP BY
                sr.GameID
            ORDER BY
                Requests DESC, gd.Title
            LIMIT
                $offset, $count";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $nextData;
        }
    } else {
        log_sql_fail();
    }

    return $retVal;
}

/**
 * Gets the number of set-less games with at least one set request.
 */
function getGamesWithRequests(array|int|null $console, int $requestStatus = RequestStatus::Any): int
{
    $query = "
        SELECT
            COUNT(DISTINCT sr.GameID) AS Games,
            sr.GameID as GameID,
            c.name as ConsoleName
        FROM
            SetRequest sr
        LEFT JOIN
            GameData gd ON (sr.GameID = gd.ID)
        LEFT JOIN
            Console c ON (gd.ConsoleID = c.ID) ";

    if ($requestStatus !== RequestStatus::Any) {
        $query .= "LEFT OUTER JOIN SetClaim sc ON (sr.GameID = sc.GameID AND sc.Status = " . ClaimStatus::Active . ") ";
    }

    $query .= "WHERE sr.GameID NOT IN (SELECT DISTINCT(GameID) FROM Achievements where Flags = '3')
               AND sr.type='" . UserGameListType::AchievementSetRequest . "'";

    if (is_array($console)) {
        $query .= ' AND c.ID IN (' . implode(',', $console) . ') ';
    } elseif (!empty($console)) {
        sanitize_sql_inputs($console);
        $query .= " AND c.ID = $console ";
    }

    if ($requestStatus === RequestStatus::Claimed) {
        $query .= " AND sc.ID IS NOT NULL ";
    } elseif ($requestStatus === RequestStatus::Unclaimed) {
        $query .= " AND sc.ID IS NULL ";
    }

    $dbResult = s_mysql_query($query);

    if (!$dbResult) {
        return 0;
    }

    return (int) mysqli_fetch_assoc($dbResult)['Games'];
}
