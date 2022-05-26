<?php

use RA\ActivityType;
use RA\ArticleType;
use RA\Permissions;
use RA\TicketState;

function getGameData($gameID): ?array
{
    sanitize_sql_inputs($gameID);
    settype($gameID, 'integer');
    if ($gameID <= 0) {
        return null;
    }
    $query = "SELECT gd.ID, gd.Title, gd.ConsoleID, gd.ForumTopicID, IFNULL( gd.Flags, 0 ) AS Flags, gd.ImageIcon, gd.ImageTitle, gd.ImageIngame, gd.ImageBoxArt, gd.Publisher, gd.Developer, gd.Genre, gd.Released, gd.IsFinal, c.Name AS ConsoleName, c.ID AS ConsoleID, gd.RichPresencePatch
              FROM GameData AS gd
              LEFT JOIN Console AS c ON gd.ConsoleID = c.ID
              WHERE gd.ID = $gameID";

    $dbResult = s_mysql_query($query);
    if ($retVal = mysqli_fetch_assoc($dbResult)) {
        settype($retVal['ID'], 'integer');
        settype($retVal['ConsoleID'], 'integer');
        settype($retVal['Flags'], 'integer');
        settype($retVal['ForumTopicID'], 'integer');
        settype($retVal['IsFinal'], 'boolean');
        return $retVal;
    } else {
        log_sql_fail();
        return null;
    }
}

function getGameTitleFromID($gameID, &$gameTitle, &$consoleID, &$consoleName, &$forumTopicID, &$allData): string
{
    sanitize_sql_inputs($gameID);
    settype($gameID, "integer");

    $gameTitle = "UNRECOGNISED";

    if ($gameID > 0) {
        $query = "SELECT gd.Title, gd.ForumTopicID, c.ID AS ConsoleID, c.Name AS ConsoleName, gd.Flags, gd.ImageIcon, gd.ImageIcon AS GameIcon, gd.ImageTitle, gd.ImageIngame, gd.ImageBoxArt, gd.Publisher, gd.Developer, gd.Genre, gd.Released
                  FROM GameData AS gd
                  LEFT JOIN Console AS c ON gd.ConsoleID = c.ID
                  WHERE gd.ID=$gameID";
        $dbResult = s_mysql_query($query);

        if ($dbResult !== false) {
            $data = mysqli_fetch_assoc($dbResult);
            if ($data !== false) {
                $gameTitle = $data['Title'];
                $consoleName = $data['ConsoleName'];
                $consoleID = $data['ConsoleID'];
                $forumTopicID = $data['ForumTopicID'];
                $allData = $data;
            }
        } else {
            log_sql_fail();
        }
    }

    return (string) $gameTitle;
}

function getGameMetadata($gameID, $user, &$achievementDataOut, &$gameDataOut, $sortBy = 0, $user2 = null, $flag = null): int
{
    return getGameMetadataByFlags($gameID, $user, $achievementDataOut, $gameDataOut, $sortBy, $user2, $flag);
}

function getGameMetadataByFlags(
    $gameID,
    $user,
    &$achievementDataOut,
    &$gameDataOut,
    $sortBy = 1,
    $user2 = null,
    $flags = 0
): int {
    sanitize_sql_inputs($gameID, $user, $user2, $flags);
    settype($gameID, 'integer');
    settype($sortBy, 'integer');
    settype($flags, 'integer');

    // flag = 5 -> Unofficial / flag = 3 -> Core
    $flags = $flags != 5 ? 3 : 5;

    $orderBy = match ($sortBy) {
        11 => "ORDER BY ach.DisplayOrder DESC, ach.ID DESC ",
        2 => "ORDER BY NumAwarded, ach.ID ASC ",
        12 => "ORDER BY NumAwarded DESC, ach.ID DESC ",
        // 3 and 13 should sort by the date the user unlocked the achievement
        // however, it's not trivial to implement (requires SQL tweaks)
        // 3 => "",
        // 13 => "",
        4 => "ORDER BY ach.Points, ach.ID ASC ",
        14 => "ORDER BY ach.Points DESC, ach.ID DESC ",
        5 => "ORDER BY ach.Title, ach.ID ASC ",
        15 => "ORDER BY ach.Title DESC, ach.ID DESC ",
        // 1
        default => "ORDER BY ach.DisplayOrder, ach.ID ASC ",
    };

    $gameDataOut = getGameData($gameID);

    $achievementDataOut = [];

    if ($gameDataOut == null) {
        return 0;
    }

    //    Get all achievements data
    //  WHERE reads: If never won, or won by a tracked gamer, or won by me
    // $query = "SELECT ach.ID, ( COUNT( aw.AchievementID ) - SUM( IFNULL( aw.HardcoreMode, 0 ) ) ) AS NumAwarded, SUM( IFNULL( aw.HardcoreMode, 0 ) ) AS NumAwardedHardcore, ach.Title, ach.Description, ach.Points, ach.TrueRatio, ach.Author, ach.DateModified, ach.DateCreated, ach.BadgeName, ach.DisplayOrder, ach.MemAddr
    //          FROM Achievements AS ach
    //          LEFT JOIN Awarded AS aw ON aw.AchievementID = ach.ID
    //          LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
    //          WHERE ( !IFNULL( ua.Untracked, FALSE ) || ua.User = \"$user\" ) AND ach.GameID = $gameID AND ach.Flags = $flags
    //          GROUP BY ach.ID
    //          $orderBy";

    $query = "
    SELECT
        ach.ID, 
        IFNULL(tracked_aw.NumAwarded, 0) AS NumAwarded,
        IFNULL(tracked_aw.NumAwardedHardcore, 0) AS NumAwardedHardcore,
        ach.Title,
        ach.Description,
        ach.Points,
        ach.TrueRatio,
        ach.Author,
        ach.DateModified,
        ach.DateCreated,
        ach.BadgeName,
        ach.DisplayOrder,
        ach.MemAddr
    FROM Achievements AS ach
    LEFT JOIN (
        SELECT
            ach.ID AS AchievementID,
            (COUNT(aw.AchievementID) - SUM(IFNULL(aw.HardcoreMode, 0))) AS NumAwarded, 
            (SUM(IFNULL(aw.HardcoreMode, 0))) AS NumAwardedHardcore
        FROM Achievements AS ach
        INNER JOIN Awarded AS aw ON aw.AchievementID = ach.ID
        INNER JOIN UserAccounts AS ua ON ua.User = aw.User
        WHERE ach.GameID = $gameID AND ach.Flags = $flags
          AND (NOT ua.Untracked" . (isset($user) ? " OR ua.User = '$user'" : "") . ")
        GROUP BY ach.ID
    ) AS tracked_aw ON tracked_aw.AchievementID = ach.ID
    WHERE ach.GameID = $gameID AND ach.Flags = $flags
    $orderBy";

    // echo $query;

    $numAchievements = 0;

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $nextID = $data['ID'];
            settype($nextID, 'integer');
            $achievementDataOut[$nextID] = $data;
            $numAchievements++;
        }
    } else {
        log_sql_fail();
        return 0;
    }

    // Now find local information:
    if (isset($user)) {
        $query = "SELECT ach.ID, aw.Date, aw.HardcoreMode
                  FROM Awarded AS aw
                  LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                  WHERE ach.GameID = $gameID AND ach.Flags = $flags AND aw.User = '$user'";

        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            while ($data = mysqli_fetch_assoc($dbResult)) {
                $nextID = $data['ID'];
                settype($nextID, 'integer');
                if (isset($data['HardcoreMode']) && $data['HardcoreMode'] == 1) {
                    $achievementDataOut[$nextID]['DateEarnedHardcore'] = $data['Date'];
                } else {
                    $achievementDataOut[$nextID]['DateEarned'] = $data['Date'];
                }
            }
        }
    }

    if (isset($user2)) {
        $query = "SELECT ach.ID, aw.Date, aw.HardcoreMode
                  FROM Awarded AS aw
                  LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                  WHERE ach.GameID = $gameID AND ach.Flags = $flags AND aw.User = '$user2'";

        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            while ($data = mysqli_fetch_assoc($dbResult)) {
                $nextID = $data['ID'];
                settype($nextID, 'integer');
                if ($data['HardcoreMode'] == 1) {
                    $achievementDataOut[$nextID]['DateEarnedFriendHardcore'] = $data['Date'];
                } else {
                    $achievementDataOut[$nextID]['DateEarnedFriend'] = $data['Date'];
                }
            }
        }
    }

    $numDistinctPlayersCasual = 0;
    $numDistinctPlayersHardcore = 0;

    $query = "SELECT aw.HardcoreMode, COUNT(DISTINCT aw.User) as Users
              FROM Awarded AS aw
              LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
              LEFT JOIN UserAccounts as ua ON ua.User = aw.User
              WHERE ach.GameID = $gameID AND ach.Flags = $flags
              AND (NOT ua.Untracked" . (isset($user) ? " OR ua.User = '$user'" : "") . ")
              GROUP BY aw.HardcoreMode";
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            if ($data['HardcoreMode'] == 1) {
                $numDistinctPlayersHardcore = $data['Users'];
            } else {
                $numDistinctPlayersCasual = $data['Users'];
            }
        }
    }

    $gameDataOut['NumAchievements'] = $numAchievements;
    $gameDataOut['NumDistinctPlayersCasual'] = $numDistinctPlayersCasual;
    $gameDataOut['NumDistinctPlayersHardcore'] = $numDistinctPlayersHardcore;

    return $numAchievements;
}

function getGameAlternatives($gameID): array
{
    sanitize_sql_inputs($gameID);
    settype($gameID, 'integer');

    $query = "SELECT gameIDAlt, gd.Title, gd.ImageIcon, c.Name AS ConsoleName,
              CASE
                WHEN (SELECT COUNT(*) FROM Achievements ach WHERE ach.GameID = gd.ID AND ach.Flags = 3) > 0 THEN 1
                ELSE 0
              END AS HasAchievements,
              (SELECT SUM(ach.Points) FROM Achievements ach WHERE ach.GameID = gd.ID AND ach.Flags = 3) AS Points, 
              gd.TotalTruePoints
              FROM GameAlternatives AS ga
              LEFT JOIN GameData AS gd ON gd.ID = ga.gameIDAlt
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE ga.gameID = $gameID
              GROUP BY gd.ID, gd.Title
              ORDER BY HasAchievements DESC, gd.Title";

    $dbResult = s_mysql_query($query);

    $results = [];

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $results[] = $data;
        }
    }

    return $results;
}

function getGamesListWithNumAchievements($consoleID, &$dataOut, $sortBy): int
{
    return getGamesListByDev(null, $consoleID, $dataOut, $sortBy);
}

function getGamesListByDev($dev, $consoleID, &$dataOut, $sortBy, $ticketsFlag = false, $filter = 0, $offset = 0, $count = 0): int
{
    sanitize_sql_inputs($dev, $consoleID, $offset, $count);

    // Specify 0 for $consoleID to fetch games for all consoles, or an ID for just that console

    $whereCond = '';
    $moreSelectCond = '';
    $havingCond = '';

    if ($ticketsFlag) {
        $selectTickets = ", ticks.OpenTickets";
        $joinTicketsTable = "
        LEFT JOIN (
            SELECT
                ach.GameID,
                count( DISTINCT tick.ID ) AS OpenTickets
            FROM
                Ticket AS tick
            LEFT JOIN
                Achievements AS ach ON ach.ID = tick.AchievementID
            WHERE
                tick.ReportState IN (" . TicketState::Open . "," . TicketState::Request . ")
            GROUP BY
                ach.GameID
        ) as ticks ON ticks.GameID = gd.ID ";
    } else {
        $selectTickets = null;
        $joinTicketsTable = null;
    }

    if ($consoleID != 0) {
        $whereCond .= "WHERE gd.ConsoleID=$consoleID ";
    }

    if ($dev != null) {
        $moreSelectCond = "SUM(CASE WHEN ach.Author LIKE '$dev' THEN 1 ELSE 0 END) AS MyAchievements,
                           SUM(CASE WHEN ach.Author NOT LIKE '$dev' THEN 1 ELSE 0 END) AS NotMyAchievements,";
        $havingCond = "HAVING MyAchievements > 0 ";
    } else {
        if ($filter == 0) { // only with achievements
            $havingCond = "HAVING NumAchievements > 0 ";
        } elseif ($filter == 1) { // only without achievements
            $havingCond = "HAVING NumAchievements = 0 ";
        }
    }

    $query = "SELECT gd.Title, gd.ID, gd.ConsoleID, c.Name AS ConsoleName, COUNT( ach.ID ) AS NumAchievements, MAX(ach.DateModified) AS DateModified, SUM(ach.Points) AS MaxPointsAvailable, lbdi.NumLBs, gd.ImageIcon as GameIcon, gd.TotalTruePoints $selectTickets,
                $moreSelectCond
                CASE WHEN LENGTH(gd.RichPresencePatch) > 0 THEN 1 ELSE 0 END AS RichPresence
                FROM GameData AS gd
                INNER JOIN Console AS c ON c.ID = gd.ConsoleID
                LEFT JOIN Achievements AS ach ON gd.ID = ach.GameID AND ach.Flags = 3
                LEFT JOIN ( SELECT lbd.GameID, COUNT( DISTINCT lbd.ID ) AS NumLBs FROM LeaderboardDef AS lbd GROUP BY lbd.GameID ) AS lbdi ON lbdi.GameID = gd.ID
                $joinTicketsTable
                $whereCond
                GROUP BY gd.ID
                $havingCond";

    settype($sortBy, 'integer');

    if ($sortBy < 1 || $sortBy > 16) {
        $sortBy = 1;
    }

    $orderBy = match ($sortBy) {
        1 => "Title",
        11 => "Title DESC",
        2 => "NumAchievements DESC, MaxPointsAvailable DESC",
        12 => "NumAchievements, MaxPointsAvailable",
        3 => "MaxPointsAvailable DESC, NumAchievements DESC",
        13 => "MaxPointsAvailable, NumAchievements",
        4 => "NumLBs DESC, MaxPointsAvailable DESC",
        14 => "NumLBs, MaxPointsAvailable",
        5 => $ticketsFlag
                ? "OpenTickets DESC "
                : "",
        15 => $ticketsFlag
                ? "OpenTickets"
                : "",
        6 => "DateModified DESC",
        16 => "DateModified",
        default => "",
    };

    if (!empty($orderBy)) {
        if (!str_contains($orderBy, "Title")) {
            if ($sortBy < 10) {
                $orderBy .= ", Title";
            } else {
                $orderBy .= ", Title DESC";
            }
        }
        if ($consoleID == 0) {
            if (str_contains($orderBy, "Title DESC")) {
                $orderBy .= ", ConsoleName DESC";
            } else {
                $orderBy .= ", ConsoleName";
            }
        }

        $query .= "ORDER BY $orderBy ";
    }

    if ($count > 0) {
        $query = substr_replace($query, "SQL_CALC_FOUND_ROWS ", 7, 0);
        $query .= " LIMIT $offset, $count";
    }

    $dataOut = [];
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[] = $db_entry;
        }
    } else {
        log_sql_fail();
    }

    $numGamesFound = count($dataOut);
    if ($count > 0) {
        if ($numGamesFound == $count) {
            $query = "SELECT FOUND_ROWS() AS NumGames";
            $dbResult = s_mysql_query($query);
            if ($dbResult !== false) {
                $numGamesFound = mysqli_fetch_assoc($dbResult)['NumGames'];
            }
        } else {
            $numGamesFound += $offset;
        }
    }

    return (int) $numGamesFound;
}

function getGamesListData($consoleID, $officialFlag = false): array
{
    sanitize_sql_inputs($consoleID);
    settype($consoleID, 'integer');

    $leftJoinAch = "";
    $whereClause = "";
    if ($officialFlag) {
        $leftJoinAch = "LEFT JOIN Achievements AS ach ON ach.GameID = gd.ID ";
        $whereClause = "WHERE ach.Flags=3 ";
    }

    // Specify 0 for $consoleID to fetch games for all consoles, or an ID for just that console
    if (isset($consoleID) && $consoleID != 0) {
        $whereClause .= $officialFlag ? "AND " : "WHERE ";
        $whereClause .= "ConsoleID=$consoleID ";
    }

    $query = "SELECT DISTINCT gd.Title, gd.ID, gd.ConsoleID, gd.ImageIcon, c.Name as ConsoleName
              FROM GameData AS gd
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              $leftJoinAch
              $whereClause
              ORDER BY ConsoleName, Title";

    $retVal = [];
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }
    return $retVal;
}

function getGamesList($consoleID, &$dataOut, $officialFlag = false): int
{
    $dataOut = getGamesListData($consoleID, $officialFlag);
    return count($dataOut);
}

function getGamesListDataNamesOnly($consoleID, $officialFlag = false): array
{
    $retval = [];

    $data = getGamesListData($consoleID, $officialFlag);

    foreach ($data as $element) {
        $retval[$element['ID']] = utf8_encode($element['Title']);
    }

    return $retval;
}

function getAchievementIDs($gameID): array
{
    sanitize_sql_inputs($gameID);
    settype($gameID, 'integer');

    $retVal = [];
    $retVal['GameID'] = $gameID;

    // Get all achievement IDs
    $query = "SELECT ach.ID AS ID
              FROM Achievements AS ach
              WHERE ach.GameID = $gameID AND ach.Flags = 3
              ORDER BY ach.ID";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $achIDs = [];
        while ($data = mysqli_fetch_assoc($dbResult)) {
            settype($data['ID'], 'integer');
            $achIDs[] = $data['ID'];
        }
        $retVal['AchievementIDs'] = $achIDs;
    }

    return $retVal;
}

function getGameIDFromTitle($gameTitleIn, $consoleID): int
{
    sanitize_sql_inputs($consoleID);
    $gameTitle = sanitizeTitle($gameTitleIn);
    settype($consoleID, 'integer');

    $query = "SELECT gd.ID
              FROM GameData AS gd
              WHERE gd.Title='$gameTitle' AND gd.ConsoleID='$consoleID'";

    $dbResult = s_mysql_query($query);
    if ($retVal = mysqli_fetch_assoc($dbResult)) {
        settype($retVal['ID'], 'integer');
        return (int) $retVal['ID'];
    } else {
        log_sql_fail();
        return 0;
    }
}

function testFullyCompletedGame($gameID, $user, $isHardcore, $postMastery): array
{
    sanitize_sql_inputs($gameID, $user, $isHardcore);
    settype($isHardcore, 'integer');

    $query = "SELECT COUNT(ach.ID) AS NumAch, COUNT(aw.AchievementID) AS NumAwarded FROM Achievements AS ach
              LEFT JOIN Awarded AS aw ON aw.AchievementID = ach.ID AND aw.User = '$user' AND aw.HardcoreMode = $isHardcore 
              WHERE ach.GameID = $gameID AND ach.Flags = 3 ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $minToCompleteGame = 5;

        $data = mysqli_fetch_assoc($dbResult);
        if ($postMastery && ($data['NumAwarded'] == $data['NumAch']) && ($data['NumAwarded'] > $minToCompleteGame)) {
            // Every achievement earned!
            // Test that this wasn't very recently posted!
            if (!RecentlyPostedCompletionActivity($user, $gameID, $isHardcore)) {
                postActivity($user, ActivityType::CompleteGame, $gameID, $isHardcore);
            }
        }

        return $data;
    }

    return [];
}

function requestModifyGameData($gameID, $developer, $publisher, $genre, $released): bool
{
    sanitize_sql_inputs($gameID, $developer, $publisher, $genre, $released);

    $query = "UPDATE GameData AS gd
              SET gd.Developer = '$developer', gd.Publisher = '$publisher', gd.Genre = '$genre', gd.Released = '$released'
              WHERE gd.ID = $gameID";

    global $db;
    $dbResult = mysqli_query($db, $query);

    if (!$dbResult) {
        log_sql_fail();
    }

    return $dbResult != null;
}

function requestModifyGame($author, $gameID, $field, $value): bool
{
    sanitize_sql_inputs($gameID, $field, $value);

    $result = false;

    settype($field, 'integer');
    switch ($field) {
        case 1: // Title
            if (!isset($value) || mb_strlen($value) < 2) {
                return false;
            }

            $newTitle = $value;
            // $newTitle = str_replace( "/", "&#47;", $newTitle );
            // $newTitle = str_replace( "\\", "&#92;", $newTitle );

            $query = "UPDATE GameData SET Title='$newTitle' WHERE ID=$gameID";

            global $db;
            $dbResult = mysqli_query($db, $query);

            $result = $dbResult !== false;
            break;

        /**
         * UPDATE: do not allow destructive actions until proper failovers are in place
         */
        // case 2: // GameHashTable
        //     $query = "DELETE FROM GameHashLibrary WHERE GameID=$gameID";
        //     log_sql( "$user: $query" );
        //     $dbResult = s_mysql_query( $query );
        //
        //     return ( $dbResult !== FALSE );
        //     break;

        case 3: // delete a single hash entry
            $query = "DELETE FROM GameHashLibrary WHERE GameID = $gameID AND MD5 = '$value'";
            $dbResult = s_mysql_query($query);

            $result = $dbResult !== false;

            // Log hash unlink
            addArticleComment("Server", ArticleType::GameHash, $gameID, $value . " unlinked by " . $author);
            break;
    }

    return $result;
}

function requestModifyGameAlt($gameID, $toAdd = null, $toRemove = null): void
{
    if (isset($toAdd)) {
        // Replace all non-numberic characters with comma so the string has a common delimiter.
        $toAdd = preg_replace("/[^0-9]+/", ",", $toAdd);
        $tok = strtok($toAdd, ",");
        $valuesArray = [];
        while ($tok !== false && $tok > 0) {
            settype($tok, 'integer');
            $valuesArray[] = "({$gameID}, {$tok}), ({$tok}, {$gameID})";
            $tok = strtok(",");
        }

        $values = implode(", ", $valuesArray);
        if (!empty($values)) {
            $query = "INSERT INTO GameAlternatives (gameID, gameIDAlt) VALUES $values ON DUPLICATE KEY UPDATE Updated = CURRENT_TIMESTAMP";
            s_mysql_query($query);
        }
    }

    if (isset($toRemove) && $toRemove > 0) {
        settype($toRemove, 'integer');
        $query = "DELETE FROM GameAlternatives
                  WHERE ( gameID = $gameID AND gameIDAlt = $toRemove ) || ( gameID = $toRemove AND gameIDAlt = $gameID )";
        s_mysql_query($query);
    }
}

function requestModifyGameForumTopic($gameID, $newForumTopic): bool
{
    sanitize_sql_inputs($gameID, $newForumTopic);
    settype($gameID, 'integer');
    settype($newForumTopic, 'integer');

    if ($gameID == 0 || $newForumTopic == 0) {
        return false;
    }

    if (getTopicDetails($newForumTopic, $topicData)) {
        global $db;
        $query = "
            UPDATE GameData AS gd
            SET gd.ForumTopicID = '$newForumTopic'
            WHERE gd.ID = $gameID";

        if (mysqli_query($db, $query)) {
            return true;
        } else {
            return false;
        }
    }
    return false;
}

/**
 * Gets the achievement distribution to display on the game page.
 */
function getAchievementDistribution(int $gameID, int $hardcore, string $requestedBy, int $flags, $numAchievements = null): array
{
    sanitize_sql_inputs($gameID, $hardcore, $requestedBy, $flags);
    settype($gameID, 'integer');
    settype($hardcore, 'integer');
    settype($flags, 'integer');
    $retval = [];

    // Returns an array of the number of players who have achieved each total, up to the max.
    $query = "
        SELECT InnerTable.AwardedCount, COUNT(*) AS NumUniquePlayers
        FROM (
            SELECT COUNT(*) AS AwardedCount
            FROM Awarded AS aw
            LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
            LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
            LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
            WHERE gd.ID = $gameID AND aw.HardcoreMode = $hardcore AND ach.Flags = " . $flags . "
              AND (NOT ua.Untracked" . (isset($requestedBy) ? " OR ua.User = '$requestedBy'" : "") . ")
            GROUP BY aw.User
            ORDER BY AwardedCount DESC
        ) AS InnerTable
        GROUP BY InnerTable.AwardedCount";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $awardedCount = $data['AwardedCount'];
            $numUnique = $data['NumUniquePlayers'];
            settype($awardedCount, 'integer');
            settype($numUnique, 'integer');
            $retval[$awardedCount] = $numUnique;
        }

        // fill the gaps and sort
        if ($numAchievements === null) {
            $numAchievements = getGameMetadataByFlags($gameID, $requestedBy, $achievementData, $gameData, 1, null, $flags);
        }

        for ($i = 1; $i <= $numAchievements; $i++) {
            if (!array_key_exists($i, $retval)) {
                $retval[$i] = 0;
            }
        }
        ksort($retval);
    }

    return $retval;
}

function getMostPopularGames($offset, $count, $method): array
{
    sanitize_sql_inputs($offset, $count, $method);
    settype($method, 'integer');

    $retval = [];

    if ($method == 0) {
        // By num awards given:
        $query = "    SELECT gd.ID, gd.Title, gd.ConsoleID, gd.ForumTopicID, gd.Flags, gd.ImageIcon, gd.ImageTitle, gd.ImageIngame, gd.ImageBoxArt, gd.Publisher, gd.Developer, gd.Genre, gd.Released, gd.IsFinal, gd.TotalTruePoints, c.Name AS ConsoleName,     SUM(NumTimesAwarded) AS NumRecords
                    FROM GameData AS gd
                    LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
                    LEFT OUTER JOIN (
                        SELECT
                            COALESCE(aw.cnt, 0) AS NumTimesAwarded,
                            GameID
                        FROM
                            Achievements AS ach
                        LEFT OUTER JOIN (
                            SELECT
                                AchievementID,
                                count(*) cnt
                            FROM
                                Awarded
                            GROUP BY
                                AchievementID) aw ON ach.ID = aw.AchievementID
                        GROUP BY
                            ach.ID) aw ON aw.GameID = gd.ID
                    GROUP BY gd.ID
                    ORDER BY NumRecords DESC
                    LIMIT $offset, $count";
    } else {
        return $retval;
        // $query = "    SELECT COUNT(*) AS NumRecords, Inner1.*
        //         FROM
        //         (
        //             SELECT gd.ID, gd.Title, gd.ConsoleID, gd.ForumTopicID, gd.Flags, gd.ImageIcon, gd.ImageTitle, gd.ImageIngame, gd.ImageBoxArt, gd.Publisher, gd.Developer, gd.Genre, gd.Released, gd.IsFinal, gd.TotalTruePoints, c.Name AS ConsoleName
        //             FROM Activity AS act
        //             LEFT JOIN GameData AS gd ON gd.ID = act.data
        //             LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
        //             WHERE act.activitytype = 3 AND !ISNULL( gd.ID )
        //             GROUP BY gd.ID, act.User
        //         ) AS Inner1
        //         GROUP BY Inner1.ID
        //         ORDER BY NumRecords DESC
        //         LIMIT $offset, $count";
    }

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $retval[] = $data;
        }
    }

    return $retval;
}

function getGameListSearch($offset, $count, $method, $consoleID = null): array
{
    sanitize_sql_inputs($offset, $count, $method, $consoleID);
    settype($method, 'integer');

    $query = null;
    $retval = [];

    if ($method == 0) {
        $where = '';
        if (isset($consoleID) && $consoleID > 0) {
            $where = "WHERE gd.ConsoleID = $consoleID ";
        }

        // By TA:
        $query = "    SELECT gd.ID, gd.Title, gd.ConsoleID, gd.ForumTopicID, gd.Flags, gd.ImageIcon, gd.ImageTitle, gd.ImageIngame, gd.ImageBoxArt, gd.Publisher, gd.Developer, gd.Genre, gd.Released, gd.TotalTruePoints, gd.IsFinal, c.Name AS ConsoleName
                    FROM GameData AS gd
                    LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
                    $where
                    ORDER BY gd.TotalTruePoints DESC
                    LIMIT $offset, $count";
    }

    if (!$query) {
        return $retval;
    }

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $retval[] = $data;
        }
    }

    return $retval;
}

function getTotalUniquePlayers($gameID, $requestedBy, $hardcoreOnly = false, $flags = null)
{
    sanitize_sql_inputs($gameID, $requestedBy);
    settype($gameID, 'integer');

    $hardcoreCond = "";
    if ($hardcoreOnly) {
        $hardcoreCond = " AND aw.HardcoreMode = 1";
    }

    $achievementStateCond = "";
    if ($flags !== null) {
        $achievementStateCond = "AND ach.Flags = $flags";
    }

    $query = "
        SELECT COUNT(DISTINCT aw.User) As UniquePlayers
        FROM Awarded AS aw
        LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
        LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
        LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
        WHERE gd.ID = $gameID
        $hardcoreCond $achievementStateCond
        AND (NOT ua.Untracked" . (isset($requestedBy) ? " OR ua.User = '$requestedBy'" : "") . ")
    ";

    $dbResult = s_mysql_query($query);

    $data = mysqli_fetch_assoc($dbResult);
    return $data['UniquePlayers'];
}

function getGameRecentPlayers($gameID, $maximum_results = 0): array
{
    sanitize_sql_inputs($gameID, $maximum_results);
    settype($gameID, 'integer');

    $retval = [];

    $query = "SELECT ua.ID as UserID, ua.User, ua.RichPresenceMsgDate AS Date, ua.RichPresenceMsg AS Activity
              FROM UserAccounts AS ua
              WHERE ua.LastGameID = $gameID AND ua.Permissions >= 0
              ORDER BY ua.RichPresenceMsgDate DESC";

    if ($maximum_results > 0) {
        $query .= " LIMIT $maximum_results";
    }

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $retval[] = $data;
        }
    }

    return $retval;
}

/**
 * Gets a game's high scorers or latest masters.
 */
function getGameTopAchievers(int $gameID, string $requestedBy): array
{
    sanitize_sql_inputs($gameID, $offset, $count, $requestedBy);

    $high_scores = [];
    $masters = [];
    $mastery_score = 0;

    $query = "SELECT SUM(Points * 2) AS Points FROM Achievements WHERE GameID = $gameID AND Flags = 3";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        if ($data = mysqli_fetch_assoc($dbResult)) {
            $mastery_score = $data['Points'];
        }
    }

    $query = "SELECT aw.User, SUM(ach.points) AS TotalScore, MAX(aw.Date) AS LastAward
                FROM Awarded AS aw
                LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
                LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
                WHERE ( !ua.Untracked OR ua.User = '$requestedBy' ) 
                  AND ach.Flags = 3 
                  AND gd.ID = $gameID
                GROUP BY aw.User
                ORDER BY TotalScore DESC, LastAward ASC";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            if (count($high_scores) < 10) {
                array_push($high_scores, $data);
            }

            if ($data['TotalScore'] == $mastery_score) {
                if (count($masters) == 10) {
                    array_shift($masters);
                }
                array_push($masters, $data);
            } elseif (count($high_scores) == 10) {
                break;
            }
        }
    }

    $retval = [];
    $retval['Masters'] = array_reverse($masters);
    $retval['HighScores'] = $high_scores;
    return $retval;
}

function getGameRankAndScore($gameID, $requestedBy): ?array
{
    sanitize_sql_inputs($gameID, $requestedBy);

    if (empty($gameID) || !isValidUsername($requestedBy)) {
        return null;
    }
    $retval = [];

    $query = "WITH data
    AS (SELECT aw.User, SUM(ach.points) AS TotalScore, MAX(aw.Date) AS LastAward,
        ROW_NUMBER() OVER (ORDER BY SUM(ach.points) DESC, MAX(aw.Date) ASC) UserRank
        FROM Awarded AS aw
        LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
        LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
        LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
        WHERE ( !ua.Untracked OR ua.User = '$requestedBy') 
          AND ach.Flags = 3 
          AND gd.ID = $gameID
        GROUP BY aw.User
        ORDER BY TotalScore DESC, LastAward ASC
   ) SELECT * FROM data WHERE User = '$requestedBy'";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $retval[] = $data;
        }
    }

    return $retval;
}

function createNewGame($titleIn, $consoleID): ?array
{
    sanitize_sql_inputs($consoleID);
    settype($consoleID, 'integer');
    $title = sanitizeTitle($titleIn);
    // $title = str_replace( "--", "-", $title );    // subtle non-comment breaker

    $query = "INSERT INTO GameData (Title, ConsoleID, ForumTopicID, Flags, ImageIcon, ImageTitle, ImageIngame, ImageBoxArt, Publisher, Developer, Genre, Released, IsFinal, RichPresencePatch, TotalTruePoints) 
                            VALUES ('$title', $consoleID, NULL, 0, '/Images/000001.png', '/Images/000002.png', '/Images/000002.png', '/Images/000002.png', NULL, NULL, NULL, NULL, 0, NULL, 0 )";

    global $db;
    $dbResult = mysqli_query($db, $query);
    if ($dbResult !== false) {
        $newID = mysqli_insert_id($db);
        static_addnewgame($newID);
        return [
            'ID' => $newID,
            'Title' => $title,
        ];
    }

    log_sql_fail();
    return null;
}

function submitNewGameTitleJSON($user, $md5, $gameIDin, $titleIn, $consoleID, $description): array
{
    $unsanitizedDescription = $description;
    sanitize_sql_inputs($user, $md5, $gameIDin, $consoleID, $description);
    settype($consoleID, 'integer');

    $retVal = [];
    $retVal['MD5'] = $md5;
    $retVal['ConsoleID'] = $consoleID;
    $retVal['GameID'] = $gameIDin;
    $retVal['GameTitle'] = $titleIn;
    $retVal['Success'] = true;

    $permissions = getUserPermissions($user);

    if (!isset($user)) {
        $retVal['Error'] = "User doesn't appear to be set or have permissions?";
        $retVal['Success'] = false;
    } elseif ($permissions < Permissions::Developer) {
        $retVal['Error'] = "You must be a developer to perform this action! Please drop a message in the forums to apply.";
        $retVal['Success'] = false;
    } elseif (mb_strlen($md5) != 32) {
        $retVal['Error'] = "MD5 provided ($md5) doesn't appear to be exactly 32 characters, this request is invalid.";
        $retVal['Success'] = false;
    } elseif (mb_strlen($titleIn) < 2) {
        $retVal['Error'] = "Cannot submit game title given as '$titleIn'";
        $retVal['Success'] = false;
    } elseif ($consoleID < 1 || (!isValidConsoleId($consoleID) && $permissions < Permissions::Admin)) {
        $retVal['Error'] = "Cannot submit game title for unknown ConsoleID $consoleID";
        $retVal['Success'] = false;
    } else {
        if (!empty($gameIDin)) {
            $game = getGameData($gameIDin);
        }
        if (empty($game)) {
            $game = getGameData(getGameIDFromTitle($titleIn, $consoleID));
        }
        $gameID = $game['ID'] ?? 0;
        if ($gameID == 0) {
            /**
             * New Game!
             * The MD5 for this game doesn't yet exist in our DB. Insert a new game:
             */
            $game = createNewGame($titleIn, $consoleID);
            $gameID = $game['ID'] ?? 0;
            if ($gameID == 0) {
                /**
                 * cannot create game $title
                 */
                $retVal['Error'] = "Failed to create game title '$titleIn'";
                $retVal['Success'] = false;
            }
        }

        if ($gameID !== 0) {
            $gameTitle = $game['Title'] ?? $titleIn;

            $retVal['GameID'] = $gameID;
            $retVal['GameTitle'] = $gameTitle;

            /**
             * Associate md5 to $gameID
             */
            $query = "INSERT INTO GameHashLibrary (MD5, GameID, User, Name) VALUES( '$md5', '$gameID', '$user', ";
            if (!empty($description)) {
                $query .= "'$description'";
            } else {
                $query .= "NULL";
            }
            $query .= " )";

            global $db;
            $dbResult = mysqli_query($db, $query);
            if ($dbResult !== false) {
                /**
                 * $user added $md5, $gameID to GameHashLibrary, and $gameID, $titleIn to GameData
                 */

                // Log hash linked
                if (!empty($unsanitizedDescription)) {
                    addArticleComment("Server", ArticleType::GameHash, $gameID, $md5 . " linked by " . $user . ". Description: \"" . $unsanitizedDescription . "\"");
                } else {
                    addArticleComment("Server", ArticleType::GameHash, $gameID, $md5 . " linked by " . $user);
                }
            } else {
                /**
                 * cannot insert duplicate md5 (already present?
                 */
                $retVal['Error'] = "Failed to add md5 for '$gameTitle' (already present?)";
                $retVal['Success'] = false;
            }
        }
    }

    settype($retVal['ConsoleID'], 'integer');
    settype($retVal['GameID'], 'integer');

    return $retVal;
}

function sanitizeTitle(string $titleIn): string
{
    // Remove single quotes, replace with double quotes:
    $title = str_replace("'", "''", $titleIn);
    $title = str_replace("/", "-", $title);

    return str_replace("\\", "-", $title);
}

function requestModifyRichPresence($gameID, $dataIn): bool
{
    sanitize_sql_inputs($gameID, $dataIn);
    settype($gameID, 'integer');

    $query = "UPDATE GameData SET RichPresencePatch='$dataIn' WHERE ID=$gameID";

    global $db;
    $dbResult = mysqli_query($db, $query);

    if ($dbResult) {
        return true;
    } else {
        return false;
    }
}

function getRichPresencePatch($gameID, &$dataOut): bool
{
    sanitize_sql_inputs($gameID);
    settype($gameID, 'integer');

    $query = "SELECT gd.RichPresencePatch FROM GameData AS gd WHERE gd.ID = $gameID ";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        $dataOut = $data['RichPresencePatch'];
        return true;
    } else {
        return false;
    }
}

/**
 * Checks to see if a user is the sole author of a set.
 */
function checkIfSoleDeveloper(string $user, int $gameID): bool
{
    sanitize_sql_inputs($user, $gameID);
    settype($gameID, 'integer');

    $query = "
        SELECT distinct(Author) AS Author FROM Achievements AS ach
        LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
        WHERE ach.GameID = $gameID
        AND ach.Flags = 3";

    $dbResult = s_mysql_query($query);

    $userFound = false;
    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            if ($user != $data['Author']) {
                return false;
            } else {
                $userFound = true;
            }
        }
    }

    return $userFound;
}
