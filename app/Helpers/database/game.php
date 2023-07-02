<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\TicketState;
use App\Platform\Enums\AchievementType;
use App\Platform\Enums\UnlockMode;
use App\Platform\Models\Game;
use App\Site\Enums\Permissions;
use Illuminate\Support\Str;

function getGameData(int $gameID): ?array
{
    if ($gameID <= 0) {
        return null;
    }

    $game = Game::with('system')->find($gameID);

    return !$game ? null : array_merge($game->toArray(), [
        'ConsoleID' => $game->system->ID,
        'ConsoleName' => $game->system->Name,
    ]);
}

// If the game is a subset, identify its parent game ID.
function getParentGameIdFromGameTitle(string $title, int $consoleID): ?int
{
    if (preg_match('/(.+)(\[Subset - .+\])/', $title, $matches)) {
        $baseSetTitle = trim($matches[1]);
        $query = "SELECT ID FROM GameData WHERE Title = :title AND ConsoleID = :consoleId";
        $result = legacyDbFetch($query, ['title' => $baseSetTitle, 'consoleId' => $consoleID]);

        return $result ? $result['ID'] : null;
    }

    return null;
}

function getParentGameIdFromGameId(int $gameID): ?int
{
    $gameData = getGameData($gameID);

    return getParentGameIdFromGameTitle($gameData['Title'], $gameData['ConsoleID']);
}

function getGameMetadata(
    int $gameID,
    ?string $user,
    ?array &$achievementDataOut,
    ?array &$gameDataOut,
    int $sortBy = 1,
    ?string $user2 = null,
    int $flags = AchievementType::OfficialCore,
    bool $metrics = false,
): int {
    $flags = $flags !== AchievementType::Unofficial ? AchievementType::OfficialCore : AchievementType::Unofficial;

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

    $metricsColumns = '';
    $metricsJoin = '';
    $metricsBindings = [];
    if ($metrics) {
        $metricsBindings = [
            'metricsGameId' => $gameID,
            'metricsAchievementType' => $flags,
        ];
        $metricsColumns = 'IFNULL(tracked_aw.NumAwarded, 0) AS NumAwarded,
                           IFNULL(tracked_aw.NumAwardedHardcore, 0) AS NumAwardedHardcore,';
        $metricsJoin = "LEFT JOIN (
            SELECT ach.ID AS AchievementID,
                (COUNT(aw.AchievementID) - SUM(IFNULL(aw.HardcoreMode, 0))) AS NumAwarded,
                (SUM(IFNULL(aw.HardcoreMode, 0))) AS NumAwardedHardcore
            FROM Achievements AS ach
            INNER JOIN Awarded AS aw ON aw.AchievementID = ach.ID
            INNER JOIN UserAccounts AS ua ON ua.User = aw.User
            WHERE ach.GameID = :metricsGameId AND ach.Flags = :metricsAchievementType AND NOT ua.Untracked
            GROUP BY ach.ID
        ) AS tracked_aw ON tracked_aw.AchievementID = ach.ID";
    }

    $query = "
    SELECT
        ach.ID,
        $metricsColumns
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
    $metricsJoin
    WHERE ach.GameID = :gameId AND ach.Flags = :achievementType
    $orderBy";

    $achievementDataOut = legacyDbFetchAll($query, array_merge([
        'gameId' => $gameID,
        'achievementType' => $flags,
    ], $metricsBindings))
        ->keyBy('ID')
        ->toArray();

    $numAchievements = count($achievementDataOut);
    foreach ($achievementDataOut as &$achievement) {
        settype($achievement['ID'], 'integer');
        settype($achievement['Points'], 'integer');
        settype($achievement['TrueRatio'], 'integer');
        settype($achievement['DisplayOrder'], 'integer');
        if ($metrics) {
            settype($achievement['NumAwarded'], 'integer');
            settype($achievement['NumAwardedHardcore'], 'integer');
        }
    }

    if (isset($user)) {
        $userUnlocks = getUserAchievementUnlocksForGame($user, $gameID, $flags);
        foreach ($userUnlocks as $achID => $userUnlock) {
            if (array_key_exists($achID, $achievementDataOut)) {
                if (array_key_exists('DateEarnedHardcore', $userUnlock)) {
                    $achievementDataOut[$achID]['DateEarnedHardcore'] = $userUnlock['DateEarnedHardcore'];
                }
                if (array_key_exists('DateEarned', $userUnlock)) {
                    $achievementDataOut[$achID]['DateEarned'] = $userUnlock['DateEarned'];
                }
            }
        }
    }

    if (isset($user2)) {
        $friendUnlocks = getUserAchievementUnlocksForGame($user2, $gameID, $flags);
        foreach ($friendUnlocks as $achID => $friendUnlock) {
            if (array_key_exists($achID, $achievementDataOut)) {
                if (array_key_exists('DateEarnedHardcore', $friendUnlock)) {
                    $achievementDataOut[$achID]['DateEarnedFriendHardcore'] = $friendUnlock['DateEarnedHardcore'];
                }
                if (array_key_exists('DateEarned', $friendUnlock)) {
                    $achievementDataOut[$achID]['DateEarnedFriend'] = $friendUnlock['DateEarned'];
                }
            }
        }
    }

    if ($metrics) {
        $parentGameId = getParentGameIdFromGameTitle($gameDataOut['Title'], $gameDataOut['ConsoleID']);

        $bindings = [
            'gameId' => $gameID,
            'achievementType' => $flags,
        ];

        $requestedByStatement = '';
        if ($user) {
            $bindings['username'] = $user;
            $requestedByStatement = 'OR ua.User = :username';
        }

        $gameIdStatement = 'ach.GameID = :gameId';
        if ($parentGameId !== null) {
            $bindings['parentGameId'] = $parentGameId;
            $gameIdStatement = 'ach.GameID IN (:gameId, :parentGameId)';
        }

        $query = "SELECT aw.HardcoreMode, COUNT(DISTINCT aw.User) as Users
                FROM (
                  SELECT aw.User, aw.HardcoreMode
                  FROM Awarded AS aw
                  LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                  LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
                  WHERE $gameIdStatement AND ach.Flags = :achievementType
                  AND (NOT ua.Untracked $requestedByStatement)
                ) AS aw
                GROUP BY aw.HardcoreMode";

        $gameMetaData = legacyDbFetchAll($query, $bindings);

        $numDistinctPlayersCasual = 0;
        $numDistinctPlayersHardcore = 0;
        foreach ($gameMetaData as $data) {
            if ($data['HardcoreMode'] == UnlockMode::Hardcore) {
                $numDistinctPlayersHardcore = $data['Users'];
            } else {
                $numDistinctPlayersCasual = $data['Users'];
            }
        }

        $gameDataOut['ParentGameID'] = $parentGameId;
        $gameDataOut['NumDistinctPlayersCasual'] = $numDistinctPlayersCasual;
        $gameDataOut['NumDistinctPlayersHardcore'] = $numDistinctPlayersHardcore;
    }

    $gameDataOut['NumAchievements'] = $numAchievements;

    return $numAchievements;
}

function getGameAlternatives(int $gameID, ?int $sortBy = null): array
{
    $orderBy = match ($sortBy) {
        11 => "ORDER BY HasAchievements ASC, gd.Title DESC",
        2 => "ORDER BY gd.TotalTruePoints DESC, gd.Title ASC ",
        12 => "ORDER BY gd.TotalTruePoints, gd.Title ASC ",
        // 1 or unspecified
        default => "ORDER BY HasAchievements DESC, SUBSTRING_INDEX(gd.Title, ' [', 1), c.Name, gd.Title ",
    };

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
              $orderBy";

    $dbResult = s_mysql_query($query);

    $results = [];

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $results[] = $data;
        }
    }

    return $results;
}

function getGamesListWithNumAchievements(int $consoleID, ?array &$dataOut, int $sortBy): int
{
    return getGamesListByDev(null, $consoleID, $dataOut, $sortBy);
}

function getGamesListByDev(
    ?string $dev,
    int $consoleID,
    ?array &$dataOut,
    int $sortBy,
    bool $ticketsFlag = false,
    ?int $filter = 0,
    int $offset = 0,
    int $count = 0
): int {
    // Specify 0 for $consoleID to fetch games for all consoles, or an ID for just that console

    $whereCond = '';
    $moreSelectCond = '';
    $havingCond = '';
    $bindings = [];
    $selectTickets = null;
    $joinTicketsTable = null;

    if ($dev != null) {
        $bindings['myAchDev'] = $dev;
        $bindings['myPtsDev'] = $dev;
        $bindings['myRRDev'] = $dev;
        $bindings['notMyAchDev'] = $dev;
        $moreSelectCond = "SUM(CASE WHEN ach.Author = :myAchDev THEN 1 ELSE 0 END) AS MyAchievements,
                           SUM(CASE WHEN ach.Author = :myPtsDev THEN ach.Points ELSE 0 END) AS MyPoints,
                           SUM(CASE WHEN ach.Author = :myRRDev THEN ach.TrueRatio ELSE 0 END) AS MyTrueRatio,
                           SUM(CASE WHEN ach.Author != :notMyAchDev THEN 1 ELSE 0 END) AS NotMyAchievements,
                           lbdi.MyLBs,";
        $havingCond = "HAVING MyAchievements > 0 ";
    } else {
        if ($filter == 0) { // only with achievements
            $havingCond = "HAVING NumAchievements > 0 ";
        } elseif ($filter == 1) { // only without achievements
            $havingCond = "HAVING NumAchievements = 0 ";
        }
    }

    if ($ticketsFlag) {
        $selectTickets = ", ticks.OpenTickets";
        $joinTicketsTable = "
        LEFT JOIN (
            SELECT
                ach.GameID,
                count( DISTINCT tick.ID ) AS OpenTickets,
                SUM(CASE WHEN ach.Author LIKE '$dev' THEN 1 ELSE 0 END) AS MyOpenTickets
            FROM
                Ticket AS tick
            LEFT JOIN
                Achievements AS ach ON ach.ID = tick.AchievementID
            WHERE
                tick.ReportState IN (" . TicketState::Open . "," . TicketState::Request . ")
            GROUP BY
                ach.GameID
        ) as ticks ON ticks.GameID = gd.ID ";
        $moreSelectCond .= "ticks.MyOpenTickets,";
    }

    if ($consoleID != 0) {
        $whereCond .= "WHERE gd.ConsoleID=$consoleID ";
    }

    // TODO slow query
    $query = "SELECT gd.Title, gd.ID, gd.ConsoleID, c.Name AS ConsoleName,
                COUNT( ach.ID ) AS NumAchievements, MAX(ach.DateModified) AS DateModified, SUM(ach.Points) AS MaxPointsAvailable,
                lbdi.NumLBs, gd.ImageIcon as GameIcon, gd.TotalTruePoints, gd.ForumTopicID $selectTickets,
                $moreSelectCond
                CASE WHEN LENGTH(gd.RichPresencePatch) > 0 THEN 1 ELSE 0 END AS RichPresence,
                CASE WHEN SUM(ach.Points) > 0 THEN ROUND(gd.TotalTruePoints/SUM(ach.Points), 2) ELSE 0.00 END AS RetroRatio
                FROM GameData AS gd
                INNER JOIN Console AS c ON c.ID = gd.ConsoleID
                LEFT JOIN Achievements AS ach ON gd.ID = ach.GameID AND ach.Flags = " . AchievementType::OfficialCore . "
                LEFT JOIN ( SELECT lbd.GameID, COUNT( DISTINCT lbd.ID ) AS NumLBs,
                                   SUM(CASE WHEN lbd.Author LIKE '$dev' THEN 1 ELSE 0 END) AS MyLBs
                            FROM LeaderboardDef AS lbd
                            GROUP BY lbd.GameID ) AS lbdi ON lbdi.GameID = gd.ID
                $joinTicketsTable
                $whereCond
                GROUP BY gd.ID
                $havingCond";

    if ($sortBy < 1 || $sortBy > 17) {
        $sortBy = 1;
    }

    $orderBy = match ($sortBy) {
        1 => "gd.Title",
        11 => "gd.Title DESC",
        2 => "NumAchievements DESC, MaxPointsAvailable DESC",
        12 => "NumAchievements, MaxPointsAvailable",
        3 => "MaxPointsAvailable DESC, NumAchievements DESC",
        13 => "MaxPointsAvailable, NumAchievements",
        4 => "NumLBs DESC, MaxPointsAvailable DESC",
        14 => "NumLBs, MaxPointsAvailable",
        5 => $ticketsFlag
                ? "ticks.OpenTickets DESC "
                : "",
        15 => $ticketsFlag
                ? "ticks.OpenTickets"
                : "",
        6 => "DateModified DESC",
        16 => "DateModified",
        7 => "RetroRatio DESC, MaxPointsAvailable DESC",
        17 => "RetroRatio ASC, MaxPointsAvailable ASC",
        default => "",
    };

    if (!empty($orderBy)) {
        if (!Str::contains($orderBy, "Title")) {
            if ($sortBy < 10) {
                $orderBy .= ", Title";
            } else {
                $orderBy .= ", Title DESC";
            }
        }
        if ($consoleID == 0) {
            if (Str::contains($orderBy, "Title DESC")) {
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

    $dataOut = legacyDbFetchAll($query, $bindings)->toArray();
    foreach ($dataOut as &$row) {
        settype($row['ID'], 'integer');
        settype($row['ConsoleID'], 'integer');
        if ($row['ForumTopicID'] !== null) {
            settype($row['ForumTopicID'], 'integer');
        }
    }

    $numGamesFound = count($dataOut);
    if ($count > 0) {
        if ($numGamesFound == $count) {
            $query = "SELECT FOUND_ROWS() AS NumGames";
            $numGamesFound = legacyDbFetch($query)['NumGames'];
        } else {
            $numGamesFound += $offset;
        }
    }

    return (int) $numGamesFound;
}

function getGamesListData(?int $consoleID = null, bool $officialFlag = false): array
{
    $leftJoinAch = "";
    $whereClause = "";
    if ($officialFlag) {
        $leftJoinAch = "LEFT JOIN Achievements AS ach ON ach.GameID = gd.ID ";
        $whereClause = "WHERE ach.Flags=" . AchievementType::OfficialCore . ' ';
    }

    // Specify 0 for $consoleID to fetch games for all consoles, or an ID for just that console
    if (!empty($consoleID)) {
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

function getGamesList(?int $consoleID, ?array &$dataOut, bool $officialFlag = false): int
{
    $dataOut = getGamesListData($consoleID, $officialFlag);

    return count($dataOut);
}

function getGamesListDataNamesOnly(int $consoleID, bool $officialFlag = false): array
{
    $retval = [];

    $data = getGamesListData($consoleID, $officialFlag);

    foreach ($data as $element) {
        $retval[$element['ID']] = utf8_encode($element['Title']);
    }

    return $retval;
}

function getGameIDFromTitle(string $gameTitle, int $consoleID): int
{
    sanitize_sql_inputs($gameTitle);

    $query = "SELECT gd.ID
              FROM GameData AS gd
              WHERE gd.Title='$gameTitle' AND gd.ConsoleID='$consoleID'";

    $dbResult = s_mysql_query($query);
    if ($retVal = mysqli_fetch_assoc($dbResult)) {
        $retVal['ID'] = (int) $retVal['ID'];

        return $retVal['ID'];
    }
    log_sql_fail();

    return 0;
}

function modifyGameData(
    string $user,
    int $gameID,
    ?string $developer,
    ?string $publisher,
    ?string $genre,
    ?string $released,
    ?string $guideURL
): bool {
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
    if ($gameData['GuideURL'] != $guideURL) {
        $modifications[] = 'Guide URL';
    }

    if (count($modifications) == 0) {
        return true;
    }

    sanitize_sql_inputs($gameID, $developer, $publisher, $genre, $released, $guideURL);

    $query = "UPDATE GameData AS gd
              SET gd.Developer = '$developer', gd.Publisher = '$publisher', gd.Genre = '$genre', gd.Released = '$released', gd.GuideURL = '$guideURL'
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

    sanitize_sql_inputs($value);

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
    $arrayFromParameter = function ($parameter): array {
        $ids = [];
        if (is_int($parameter)) {
            $ids[] = $parameter;
        } elseif (is_string($parameter)) {
            // Replace all non-numeric characters with comma so the string has a common delimiter.
            $toAdd = preg_replace("/[^0-9]+/", ",", $parameter);
            $tok = strtok($toAdd, ",");
            while ($tok !== false && $tok > 0) {
                $ids[] = (int) $tok;
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

    if (!getTopicDetails($newForumTopic)) {
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

function getGameListSearch(int $offset, int $count, int $method, ?int $consoleID = null): array
{
    $query = null;
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
        return [];
    }

    $dbResult = s_mysql_query($query);

    $retval = [];
    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $retval[] = $data;
        }
    }

    return $retval;
}

function createNewGame(string $title, int $consoleID): ?array
{
    sanitize_sql_inputs($title, $consoleID);
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

function submitNewGameTitleJSON(
    string $user,
    string $md5,
    ?int $gameIDin,
    string $titleIn,
    int $consoleID,
    ?string $description
): array {
    $unsanitizedDescription = $description;
    sanitize_sql_inputs($user, $md5, $description);

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
        $gameID = (int) ($game['ID'] ?? 0);
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

function getRichPresencePatch(int $gameID, ?array &$dataOut): bool
{
    $query = "SELECT gd.RichPresencePatch FROM GameData AS gd WHERE gd.ID = $gameID ";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $data = mysqli_fetch_assoc($dbResult);
        $dataOut = $data['RichPresencePatch'];

        return true;
    }

    return false;
}

function getRandomGameWithAchievements(): int
{
    $maxID = legacyDbFetch("SELECT MAX(Id) AS MaxID FROM GameData")['MaxID'];

    // This seems to be the most efficient way to randomly pick a game with achievements.
    // Each iteration of this loop takes less than 1ms, whereas alternate implementations that
    // scanned the table using "LIMIT RAND(),1" or "ORDER BY RAND() LIMIT 1" took upwards of
    // 400ms as the calculation for number of achievements had to be done for every row skipped.
    // This logic could be revisited after denormalized achievement counts exist.
    // With 25k rows in the GameData table, and 6k games with achievements, the chance of any
    // individual query failing is roughly 75%. The chance of three queries in a row failing is
    // 42%. At ten queries, the chance is way down at 6%, and we're still 40+ times faster than
    // the alternate solutions.
    do {
        $gameID = random_int(1, $maxID);
        $query = "SELECT gd.ConsoleID, COUNT(ach.ID) AS NumAchievements
                FROM GameData gd LEFT JOIN Achievements ach ON ach.GameID=gd.ID
                WHERE ach.Flags = " . AchievementType::OfficialCore . " AND gd.ConsoleID < 100
                AND gd.ID = $gameID
                GROUP BY ach.GameID
                HAVING NumAchievements > 0";

        $dbResult = legacyDbFetch($query);
    } while ($dbResult === null); // game has no achievements or is associated to hub/event console

    return $gameID;
}
