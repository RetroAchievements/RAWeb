<?php

use App\Community\Enums\ClaimStatus;
use App\Community\Enums\RequestStatus;
use App\Community\Enums\UserGameListType;
use App\Models\User;
use App\Models\UserGameListEntry;

/**
 * Gets the total and remaining set requests left for the given user.
 */
function getUserRequestsInformation(User $user, int $gameId = -1): array
{
    $requests = UserGameListEntry::getUserSetRequestsInformation($user);

    $requests['used'] = 0;
    $requests['requestedThisGame'] = 0;

    // Determine how many of the users current requests are still valid.
    // Requests made for games that since received achievements do not count towards a used request
    $setRequests = UserGameListEntry::where('user_id', $user->id)
        ->where('type', UserGameListType::AchievementSetRequest)
        ->join('GameData', 'GameData.ID', '=', 'GameId')
        ->select(['GameData.ID', 'GameData.achievements_published']);
    foreach ($setRequests->get() as $request) {
        // If the game does not have achievements then it counts as a legit request
        if ($request['achievements_published'] == 0) {
            $requests['used']++;
        }

        // Determine if we have made a request for the input game
        if ($request['ID'] == $gameId) {
            $requests['requestedThisGame'] = 1;
        }
    }

    $requests['remaining'] = $requests['total'] - $requests['used'];

    return $requests;
}

/**
 * Gets the number of set requests for a given game.
 */
function getSetRequestCount(int $gameId): int
{
    if ($gameId < 1) {
        return 0;
    }

    return UserGameListEntry::where("GameID", $gameId)
        ->where("type", UserGameListType::AchievementSetRequest)
        ->count();
}

/**
 * Gets a list of set requestors for a given game.
 */
function getSetRequestorsList(int $gameId, bool $getEmailInfo = false): array
{
    if ($gameId < 1) {
        return [];
    }

    $query = UserGameListEntry::where('GameID', $gameId)
        ->where('type', UserGameListType::AchievementSetRequest)
        ->with('user');

    if ($getEmailInfo) {
        $query->with(['game:ID,title']);
    }

    $setRequests = $query->get();

    $processedValues = $setRequests->map(function ($setRequest) use ($getEmailInfo) {
        $record = [
            'Requestor' => $setRequest->user->User,
        ];

        if ($getEmailInfo) {
            $record['Email'] = $setRequest->user->EmailAddress;
            $record['Title'] = $setRequest->game->Title;
        }

        return $record;
    });

    return $processedValues->toArray();
}

/**
 * Gets a list of the most requested sets without core achievements.
 */
function getMostRequestedSetsList(array|int|null $console, int $offset, int $count, int $requestStatus = RequestStatus::Any): array
{
    $retVal = [];

    $query = "
        SELECT
            COUNT(DISTINCT(sr.user_id)) AS Requests,
            sr.GameID as GameID,
            gd.Title as GameTitle,
            gd.ImageIcon as GameIcon,
            c.name as ConsoleName,
            GROUP_CONCAT(DISTINCT(IF(sc.Status IN (" . ClaimStatus::Active . ',' . ClaimStatus::InReview . "), sc.User, NULL))) AS Claims
        FROM
            SetRequest sr
        LEFT JOIN
            SetClaim sc ON (sr.GameID = sc.game_id AND sc.Status IN (" . ClaimStatus::Active . ',' . ClaimStatus::InReview . "))
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
        $query .= "LEFT OUTER JOIN SetClaim sc ON (sr.GameID = sc.game_id AND sc.Status IN (" . ClaimStatus::Active . ',' . ClaimStatus::InReview . ")) ";
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

function getUserGameListsContaining(User $user, int $gameId): array
{
    return UserGameListEntry::where("user_id", $user->id)
        ->where("GameID", $gameId)
        ->get(["type"])
        ->pluck("type")
        ->toArray();
}
