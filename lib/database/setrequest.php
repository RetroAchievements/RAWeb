<?php
/**
 * Gets a list of set requests made by a given user.
 *
 * @param string $user the user to get a list of set requests from
 * @return array
 */
function getUserRequestList($user)
{
    $retVal = [];

    $query = "
        SELECT
            sr.GameID as GameID,
            gd.Title as GameTitle,
            gd.ImageIcon as GameIcon,
            c.name as ConsoleName
        FROM
            SetRequest sr
        LEFT JOIN
            GameData gd ON (sr.GameID = gd.ID)
        LEFT JOIN
            Console c ON (gd.ConsoleID = c.ID)
        WHERE
            sr.user = '$user'
        ORDER BY
            GameTitle ASC";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $nextData;
        }
    } else {
        // error_log(__FUNCTION__ . " failed?!");
        log_sql_fail();
    }

    return $retVal;
}

/**
 * Gets the total and remaining set requests left for the given user.
 *
 * @param string $user the user to get set request information for
 * @param array $list input list of set requests
 * @param int $gameID the game to check if a the user has made a set request for
 * @return array
 */
function getUserRequestsInformation($user, $list, $gameID = -1)
{
    $requests = [];
    $requests['total'] = 0;
    $requests['used'] = 0;
    $requests['requestedThisGame'] = 0;
    $points = GetScore($user);
    $age = GetAge($user);

    //Determine how many requests the user can make
    if ($points >= 2500) {
        $requests['total']++;
    }
    if ($points >= 5000) {
        $requests['total']++;
    }
    if ($points >= 10000) {
        $requests['total']++;
    }
    if ($points >= 15000) {
        $requests['total']++;
    }
    if ($points >= 20000) {
        $requests['total']++;
        $requests['total'] += floor(($points - 20000) / 10000);
    }
    if ($points >= 190000) {
        $requests['total']--;
        $requests['total'] -= floor(($points - 190000) / 20000);
    }
    $requests['total'] += $age;

    //Determine how many of the users current requests are still valid.
    //Requests made for games that now have achievements do no count towards a used request
    foreach ($list as $request) {
        //If the game does not have achievements then it couns as a legit request
        if (count(getAchievementIDs($request['GameID'])['AchievementIDs']) == 0) {
            $requests['used']++;
        }

        //Determine if we have made a request for the input game
        if ($request['GameID'] == $gameID) {
            $requests['requestedThisGame'] = 1;
        }
    }

    $requests['remaining'] = $requests['total'] - $requests['used'];

    return $requests;
}

/**
 * Toggles a user set request.
 * If the user has not requested the set then add an entry to the database.
 * If the user has requested the set then remove it from the database.
 *
 * @param string $user the user to toggle a set request for
 * @param int $gameID
 * @param int $remaining remaining set requests for the user
 * @return bool
 */
function toggleSetRequest($user, $gameID, $remaining)
{
    $query = "
        SELECT
            COUNT(*) FROM SetRequest 
        WHERE
            User = '$user'
        AND
            GameID = '$gameID'";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        if (mysqli_fetch_assoc($dbResult)['COUNT(*)'] == 1) {
            $query2 = "
                DELETE
                    FROM SetRequest
                WHERE
                    (`User` = '$user')
                AND
                    (`GameID` = '$gameID')";

            // error_log($query2);
            if (s_mysql_query($query2)) {
                return true;
            } else {
                return false;
            }
        } else {
            //Only insert a set request if the user has some available
            if ($remaining > 0) {
                $query2 = "
                    INSERT
                        INTO SetRequest (`User`, `GameID`)
                    VALUES ('$user', '$gameID')";
                // error_log($query2);
                if (s_mysql_query($query2)) {
                    return true;
                } else {
                    return false;
                }
            }
        }
    }
    return false;
}

/**
 * Gets the number of set requests for a given game.
 *
 * @param int $gameID the game to get the number of set requests for
 * @return int
 */
function getSetRequestCount($gameID)
{
    settype($gameID, 'integer');
    if ($gameID < 1) {
        return 0;
    }

    $query = "SELECT COUNT(*) AS Request FROM
                SetRequest
                WHERE GameID = $gameID";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        return (int)(mysqli_fetch_assoc($dbResult)['Request'] ?? 0);
    } else {
        return 0;
    }
}

/**
 * Gets a list of set requestors for a given game.
 *
 * @param int $gameID the game to get set requestors for
 * @return array|bool
 */
function getSetRequestorsList($gameID)
{
    $retVal = [];

    settype($gameID, 'integer');

    if ($gameID < 1) {
        return false;
    }

    $query = "
        SELECT
            User AS Requestor
        FROM
            SetRequest
        WHERE
            GameID = $gameID";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $nextData;
        }
    } else {
        // error_log(__FUNCTION__ . " failed?!");
        log_sql_fail();
    }

    return $retVal;
}

/**
 * Gets a list of the most requested sets without core achievements.
 *
 * @param int $console the console to get games for
 * @param int $offset offset starting position for returned games
 * @param int $count number of games to return
 * @return array
 */
function getMostRequestedSetsList($console, $offset, $count)
{
    $retVal = [];

    $query = "
        SELECT
            COUNT(*) AS Requests,
            sr.GameID as GameID,
            gd.Title as GameTitle,
            gd.ImageIcon as GameIcon,
            c.name as ConsoleName
        FROM
            SetRequest sr
        LEFT JOIN
            GameData gd ON (sr.GameID = gd.ID)
        LEFT JOIN
            Console c ON (gd.ConsoleID = c.ID)
        WHERE 
            sr.GameID NOT IN (SELECT DISTINCT(GameID) FROM Achievements where Flags = '3') ";

    if ($console !== null) {
        $query .= "
                AND c.ID = '$console' ";
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
        // error_log(__FUNCTION__ . " failed?!");
        log_sql_fail();
    }

    return $retVal;
}

/**
 * Gets the number of set-less games with at least one set request.
 *
 * @param int $console the console to get game count for
 * @return bool|mixed|string
 */
function getGamesWithRequests($console)
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
            Console c ON (gd.ConsoleID = c.ID)
        WHERE
             GameID NOT IN (SELECT DISTINCT(GameID) FROM Achievements where Flags = '3') ";

    if ($console != null) {
        $query .= "
                AND c.ID = '$console' ";
    }

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult)['Games'];
    } else {
        return false;
    }
}
