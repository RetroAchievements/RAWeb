<?php

use LegacyApp\Community\Enums\ArticleType;
use LegacyApp\Community\Enums\TicketState;
use LegacyApp\Platform\Enums\AchievementType;
use LegacyApp\Platform\Enums\UnlockMode;
use LegacyApp\Site\Enums\Permissions;

function getGameData(int $gameID): ?array
{
    if ($gameID <= 0) {
        return null;
    }

    $query = "SELECT gd.ID, gd.Title, gd.ConsoleID, gd.ForumTopicID, IFNULL( gd.Flags, 0 ) AS Flags, gd.ImageIcon, gd.ImageTitle, gd.ImageIngame, gd.ImageBoxArt, gd.Publisher, gd.Developer, gd.Genre, gd.Released, gd.IsFinal, c.Name AS ConsoleName, c.ID AS ConsoleID, gd.RichPresencePatch
              FROM GameData AS gd
              LEFT JOIN Console AS c ON gd.ConsoleID = c.ID
              WHERE gd.ID = $gameID";

    $retVal = legacyDbFetch($query);
    if ($retVal) {
        settype($retVal['ID'], 'integer');
        settype($retVal['ConsoleID'], 'integer');
        settype($retVal['Flags'], 'integer');
        settype($retVal['ForumTopicID'], 'integer');
        settype($retVal['IsFinal'], 'boolean');
    }

    return $retVal;
}

function getGameTitleFromID($gameID, &$gameTitle, &$consoleID, &$consoleName, &$forumTopicID, &$allData): bool
{
    sanitize_sql_inputs($gameID);
    settype($gameID, "integer");

    $gameTitle = "UNRECOGNISED";

    if (empty($gameID)) {
        return false;
    }
    $query = "SELECT gd.ID, gd.Title, gd.ForumTopicID, c.ID AS ConsoleID, c.Name AS ConsoleName, gd.Flags, gd.ImageIcon, gd.ImageIcon AS GameIcon, gd.ImageTitle, gd.ImageIngame, gd.ImageBoxArt, gd.Publisher, gd.Developer, gd.Genre, gd.Released
              FROM GameData AS gd
              LEFT JOIN Console AS c ON gd.ConsoleID = c.ID
              WHERE gd.ID=$gameID";
    $dbResult = s_mysql_query($query);

    if (!$dbResult) {
        log_sql_fail();

        return false;
    }

    $data = mysqli_fetch_assoc($dbResult);
    if (empty($data)) {
        return false;
    }

    $gameTitle = $data['Title'];
    $consoleName = $data['ConsoleName'];
    $consoleID = $data['ConsoleID'];
    $forumTopicID = $data['ForumTopicID'];
    $allData = $data;

    return true;
}

function getGameMetadata($gameID, $user, &$achievementDataOut, &$gameDataOut, $sortBy = 0, $user2 = null, $flag = null): int
{
    return getGameMetadataByFlags($gameID, $user, $achievementDataOut, $gameDataOut, $sortBy, $user2, $flag);
}

function getGameMetadataByFlags(
    $gameID,
    ?string $user,
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
                if (isset($data['HardcoreMode']) && $data['HardcoreMode'] == UnlockMode::Hardcore) {
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
                if ($data['HardcoreMode'] == UnlockMode::Hardcore) {
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
            if ($data['HardcoreMode'] == UnlockMode::Hardcore) {
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
                WHEN (SELECT COUNT(*) FROM Achievements ach WHERE ach.GameID = gd.ID AND ach.Flags = " . AchievementType::OfficialCore . ") > 0 THEN 1
                ELSE 0
              END AS HasAchievements,
              (SELECT SUM(ach.Points) FROM Achievements ach WHERE ach.GameID = gd.ID AND ach.Flags = " . AchievementType::OfficialCore . ") AS Points,
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

    $query = "SELECT gd.Title, gd.ID, gd.ConsoleID, c.Name AS ConsoleName,
                COUNT( ach.ID ) AS NumAchievements, MAX(ach.DateModified) AS DateModified, SUM(ach.Points) AS MaxPointsAvailable,
                lbdi.NumLBs, gd.ImageIcon as GameIcon, gd.TotalTruePoints, gd.ForumTopicID $selectTickets,
                $moreSelectCond
                CASE WHEN LENGTH(gd.RichPresencePatch) > 0 THEN 1 ELSE 0 END AS RichPresence
                FROM GameData AS gd
                INNER JOIN Console AS c ON c.ID = gd.ConsoleID
                LEFT JOIN Achievements AS ach ON gd.ID = ach.GameID AND ach.Flags = " . AchievementType::OfficialCore . "
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
            if ($db_entry['ForumTopicID'] != null) {
                settype($db_entry['ForumTopicID'], 'integer');
            }
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
        $whereClause = "WHERE ach.Flags=" . AchievementType::OfficialCore . ' ';
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

function getGameIDFromTitle($gameTitle, $consoleID): int
{
    sanitize_sql_inputs($gameTitle, $consoleID);
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

function modifyGameData(string $user, int $gameID, ?string $developer,
    ?string $publisher, ?string $genre, ?string $released): bool
{
    $gameData = getGameData($gameID);
    if (empty($gameData)) {
        return false;
    }

    $modifications = [];
    if ($gameData['Developer'] != $developer) {
        $modifications[] = 'developer';
    }
    if ($gameData['Publisher'] != $publisher) {
        $modifications[] = 'publisher';
    }
    if ($gameData['Genre'] != $genre) {
        $modifications[] = 'genre';
    }
    if ($gameData['Released'] != $released) {
        $modifications[] = 'first released';
    }

    if (count($modifications) == 0) {
        return true;
    }

    sanitize_sql_inputs($gameID, $developer, $publisher, $genre, $released);

    $query = "UPDATE GameData AS gd
              SET gd.Developer = '$developer', gd.Publisher = '$publisher', gd.Genre = '$genre', gd.Released = '$released'
              WHERE gd.ID = $gameID";

    $db = getMysqliConnection();
    $dbResult = mysqli_query($db, $query);

    if (!$dbResult) {
        log_sql_fail();
    }

    addArticleComment('Server', ArticleType::GameModification, $gameID, "$user changed the " .
        implode(', ', $modifications) . ((count($modifications) == 1) ? " field" : " fields"));

    return $dbResult != null;
}

function modifyGameTitle(string $user, int $gameID, string $value): bool
{
    if (mb_strlen($value) < 2) {
        return false;
    }

    sanitize_sql_inputs($gameID, $value);

    $query = "UPDATE GameData SET Title='$value' WHERE ID=$gameID";

    $db = getMysqliConnection();
    if (!mysqli_query($db, $query)) {
        return false;
    }

    addArticleComment('Server', ArticleType::GameModification, $gameID, "$user changed the game name");

    return true;
}

function modifyGameAlternatives(string $user, int $gameID, int|string|null $toAdd = null, int|string|array|null $toRemove = null): void
{
    $arrayFromParameter = function ($parameter) {
        $ids = [];
        if (is_int($parameter)) {
            $ids[] = $parameter;
        } elseif (is_string($parameter)) {
            // Replace all non-numeric characters with comma so the string has a common delimiter.
            $toAdd = preg_replace("/[^0-9]+/", ",", $parameter);
            $tok = strtok($toAdd, ",");
            while ($tok !== false && $tok > 0) {
                settype($tok, 'integer');
                $ids[] = $tok;
                $tok = strtok(",");
            }
        } elseif (is_array($parameter)) {
            foreach ($parameter as $id) {
                $ids[] = (int) $id;
            }
        }

        return $ids;
    };

    $createAuditLogEntries = function (string $action, array $ids) use ($user, $gameID) {
        $message = (count($ids) == 1) ? "$user $action related game id " . $ids[0] :
            "$user $action related game ids: " . implode(', ', $ids);

        addArticleComment('Server', ArticleType::GameModification, $gameID, $message);

        $message = "$user $action related game id $gameID";
        foreach ($ids as $id) {
            addArticleComment('Server', ArticleType::GameModification, $id, $message);
        }
    };

    if (!empty($toAdd)) {
        $ids = $arrayFromParameter($toAdd);
        if (!empty($ids)) {
            $valuesArray = [];
            foreach ($ids as $id) {
                $valuesArray[] = "({$gameID}, {$id}), ({$id}, {$gameID})";
            }
            $values = implode(", ", $valuesArray);

            $query = "INSERT INTO GameAlternatives (gameID, gameIDAlt) VALUES $values ON DUPLICATE KEY UPDATE Updated = CURRENT_TIMESTAMP";
            s_mysql_query($query);

            $createAuditLogEntries('added', $ids);
        }
    }

    if (!empty($toRemove)) {
        $ids = $arrayFromParameter($toRemove);
        if (!empty($ids)) {
            $values = implode(',', $ids);
            $query = "DELETE FROM GameAlternatives
                      WHERE ( gameID = $gameID AND gameIDAlt IN ($values) ) || ( gameIDAlt = $gameID AND gameID IN ($values) )";
            s_mysql_query($query);

            $createAuditLogEntries('removed', $ids);
        }
    }
}

function modifyGameForumTopic(string $user, int $gameID, int $newForumTopic): bool
{
    sanitize_sql_inputs($gameID, $newForumTopic);

    if ($gameID == 0 || $newForumTopic == 0) {
        return false;
    }

    if (!getTopicDetails($newForumTopic, $topicData)) {
        return false;
    }

    $db = getMysqliConnection();
    $query = "UPDATE GameData SET ForumTopicID = $newForumTopic WHERE ID = $gameID";
    echo $query;

    if (!mysqli_query($db, $query)) {
        return false;
    }

    addArticleComment('Server', ArticleType::GameModification, $gameID, "$user changed the forum topic");

    return true;
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

function createNewGame($title, $consoleID): ?array
{
    sanitize_sql_inputs($title, $consoleID);
    settype($consoleID, 'integer');
    // $title = str_replace( "--", "-", $title );    // subtle non-comment breaker

    $query = "INSERT INTO GameData (Title, ConsoleID, ForumTopicID, Flags, ImageIcon, ImageTitle, ImageIngame, ImageBoxArt, Publisher, Developer, Genre, Released, IsFinal, RichPresencePatch, TotalTruePoints)
                            VALUES ('$title', $consoleID, NULL, 0, '/Images/000001.png', '/Images/000002.png', '/Images/000002.png', '/Images/000002.png', NULL, NULL, NULL, NULL, 0, NULL, 0 )";

    $db = getMysqliConnection();
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
                /*
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

            $db = getMysqliConnection();
            $dbResult = mysqli_query($db, $query);
            if ($dbResult !== false) {
                /*
                 * $user added $md5, $gameID to GameHashLibrary, and $gameID, $titleIn to GameData
                 */

                // Log hash linked
                if (!empty($unsanitizedDescription)) {
                    addArticleComment("Server", ArticleType::GameHash, $gameID, $md5 . " linked by " . $user . ". Description: \"" . $unsanitizedDescription . "\"");
                } else {
                    addArticleComment("Server", ArticleType::GameHash, $gameID, $md5 . " linked by " . $user);
                }
            } else {
                /*
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

function modifyGameRichPresence(string $user, int $gameID, string $dataIn): bool
{
    getRichPresencePatch($gameID, $existingData);
    if ($existingData == $dataIn) {
        return true;
    }

    sanitize_sql_inputs($gameID, $dataIn);
    $query = "UPDATE GameData SET RichPresencePatch='$dataIn' WHERE ID=$gameID";

    $db = getMysqliConnection();
    $dbResult = mysqli_query($db, $query);
    if (!$dbResult) {
        return false;
    }

    addArticleComment('Server', ArticleType::GameModification, $gameID, "$user changed the rich presence script");

    return true;
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
