<?php

use App\Community\Enums\ArticleType;
use App\Platform\Actions\TrimGameMetadata;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerGame;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
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

function getGameMetadata(
    int $gameID,
    ?User $user,
    ?array &$achievementDataOut,
    ?array &$gameDataOut,
    int $sortBy = 1,
    ?User $user2 = null,
    int $flag = AchievementFlag::OfficialCore,
    bool $metrics = false,
): int {
    $flag = $flag !== AchievementFlag::Unofficial ? AchievementFlag::OfficialCore : AchievementFlag::Unofficial;

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

        6 => "ORDER BY
            CASE
                WHEN ach.type = 'progression' THEN 0
                WHEN ach.type = 'win_condition' THEN 1
                WHEN ach.type = 'missable' THEN 2
                WHEN ach.type IS NULL THEN 3
                ELSE 4
            END,
            ach.DisplayOrder,
            ach.ID ASC ",

        16 => "ORDER BY
            CASE
                WHEN ach.type = 'progression' THEN 0
                WHEN ach.type = 'win_condition' THEN 1
                WHEN ach.type = 'missable' THEN 2
                WHEN ach.type IS NULL THEN 3
                ELSE 4
            END DESC,
            ach.DisplayOrder DESC,
            ach.ID DESC ",

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
        $parentGameId = getParentGameIdFromGameTitle($gameDataOut['Title'], $gameDataOut['ConsoleID']);

        $query = "SELECT players_total AS NumDistinctPlayers FROM GameData WHERE ID=" . ($parentGameId ?? $gameID);
        $gameMetrics = legacyDbFetch($query);

        $gameDataOut['ParentGameID'] = $parentGameId;
        $gameDataOut['NumDistinctPlayers'] = $gameMetrics['NumDistinctPlayers'] ?? 0;

        $metricsColumns = 'ach.unlocks_total AS NumAwarded, ach.unlocks_hardcore_total AS NumAwardedHardcore,';
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
        ach.MemAddr,
        ach.type
    FROM Achievements AS ach
    $metricsJoin
    WHERE ach.GameID = :gameId AND ach.Flags = :achievementFlag AND ach.deleted_at IS NULL
    $orderBy";

    $achievementDataOut = legacyDbFetchAll($query, array_merge([
        'gameId' => $gameID,
        'achievementFlag' => $flag,
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

    if ($user) {
        $userUnlocks = getUserAchievementUnlocksForGame($user, $gameID, $flag);
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

    if ($user2) {
        $friendUnlocks = getUserAchievementUnlocksForGame($user2, $gameID, $flag);
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
        default => "ORDER BY HasAchievements DESC, " . ifStatement("gd.Title LIKE '~%'", 1, 0) . ", SUBSTRING_INDEX(gd.Title, ' [', 1), c.Name, gd.Title ",
    };

    $query = "SELECT gameIDAlt, gd.Title, gd.ImageIcon, c.Name AS ConsoleName,
              CASE
                WHEN (SELECT COUNT(*) FROM Achievements ach WHERE ach.GameID = gd.ID AND ach.Flags = " . AchievementFlag::OfficialCore . ") > 0 THEN 1
                ELSE 0
              END AS HasAchievements,
              (SELECT SUM(ach.Points) FROM Achievements ach WHERE ach.GameID = gd.ID AND ach.Flags = " . AchievementFlag::OfficialCore . ") AS Points,
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

function getGamesListByDev(
    ?string $dev,
    int $consoleID,
    ?array &$dataOut,
    int $sortBy,
    bool $ticketsFlag = false,
    ?int $filter = 0,
    int $offset = 0,
    int $count = 0,
    ?string $listType = null
): int {
    $dataOut = [];
    $numGamesFound = 0;

    $gameIds = [];
    $gameList = '';
    $bindings = [];

    if ($sortBy < 10) {
        $titleSort = ifStatement("gd.Title LIKE '~%'", 1, 0) . ", gd.Title";
    } else {
        $titleSort = ifStatement("gd.Title LIKE '~%'", 0, 1) . ", gd.Title DESC";
    }

    // Specify 0 for $consoleID to fetch games for all consoles, or an ID for just that console
    $whereClause = '';
    if ($consoleID !== 0) {
        $whereClause = "AND gd.ConsoleID = $consoleID";
    } elseif ($sortBy < 10) {
        $titleSort .= ", c.Name ";
    } else {
        $titleSort .= ", c.Name DESC";
    }

    if ($dev === null) {
        $whereClause .= match ($filter) {
            0 => ' AND gd.achievements_published > 0', // only with achievements
            1 => ' AND COALESCE(gd.achievements_published,0) = 0', // only without achievements
            default => '', // both
        };
    }

    $listJoin = '';
    if ($listType !== null) {
        $listJoin = "INNER JOIN SetRequest sr ON sr.GameID = gd.ID";
        $whereClause .= " AND sr.user_id = " . request()->user()->ID . " AND sr.type = :listType";
        $bindings['listType'] = $listType;
    }

    $orderBy = match ($sortBy) {
        1 => "ORDER BY $titleSort",
        11 => "ORDER BY $titleSort",
        2 => "ORDER BY NumAchievements DESC, MaxPointsAvailable DESC, $titleSort",
        12 => "ORDER BY NumAchievements, MaxPointsAvailable, $titleSort",
        3 => "ORDER BY MaxPointsAvailable DESC, NumAchievements DESC, $titleSort",
        13 => "ORDER BY MaxPointsAvailable, NumAchievements, $titleSort",
        4 => "ORDER BY NumLBs DESC, MaxPointsAvailable DESC, $titleSort",
        14 => "ORDER BY NumLBs, MaxPointsAvailable, $titleSort",
        5 => $ticketsFlag ? "ORDER BY OpenTickets DESC, $titleSort" : '',
        15 => $ticketsFlag ? "ORDER BY OpenTickets, $titleSort" : '',
        6 => "ORDER BY DateModified DESC, $titleSort",
        16 => "ORDER BY DateModified, $titleSort",
        7 => "ORDER BY RetroRatio DESC, MaxPointsAvailable DESC, $titleSort",
        17 => "ORDER BY RetroRatio ASC, MaxPointsAvailable ASC, $titleSort",
        default => "",
    };

    $foundRows = '';
    if ($count > 0 || $offset > 0) {
        $orderBy .= " LIMIT $offset, $count";
        $foundRows = 'SQL_CALC_FOUND_ROWS';
    }

    $initialQuery = true;
    if ($sortBy === 4 || $sortBy === 14) { // NumLBs
        $query = "SELECT $foundRows gd.ID, gd.points_total AS MaxPointsAvailable, SUM(!ISNULL(lb.ID)) AS NumLBs
                  FROM GameData gd
                  INNER JOIN Console c ON c.ID = gd.ConsoleID $listJoin
                  LEFT JOIN LeaderboardDef lb ON lb.GameID = gd.ID
                  WHERE 1=1 $whereClause
                  GROUP BY gd.ID, gd.points_total $orderBy";
    } elseif ($sortBy === 5 || $sortBy === 15) { // OpenTickets
        $query = "SELECT $foundRows gd.ID, SUM(!ISNULL(tick.ID)) AS OpenTickets
                  FROM GameData gd
                  INNER JOIN Console c ON c.ID = gd.ConsoleID $listJoin
                  LEFT JOIN Achievements ach ON ach.GameID=gd.ID
                  LEFT JOIN Ticket tick ON tick.AchievementID=ach.ID AND tick.ReportState IN (1,3)
                  WHERE 1=1 $whereClause
                  GROUP BY gd.ID $orderBy";
    } elseif ($sortBy === 6 || $sortBy === 16) { // DateModified
        $query = "SELECT $foundRows gd.ID, MAX(ach.DateModified) AS DateModified
                  FROM GameData gd
                  INNER JOIN Console c ON c.ID = gd.ConsoleID $listJoin
                  LEFT JOIN Achievements ach ON ach.GameID=gd.ID AND ach.Flags=" . AchievementFlag::OfficialCore . "
                  WHERE 1=1 $whereClause
                  GROUP BY gd.ID $orderBy";
    } else {
        // other sorts can be handled without an initial query
        $initialQuery = false;
    }

    if ($initialQuery) {
        foreach (legacyDbFetchAll($query, $bindings) as $row) {
            $gameIds[] = $row['ID'];
        }

        if (empty($gameIds)) {
            return 0;
        }
        $gameList = implode(',', $gameIds);

        $numGamesFound = count($gameIds);
        if ($count > 0) {
            if ($numGamesFound == $count) {
                $query = "SELECT FOUND_ROWS() AS NumGames";
                $numGamesFound = legacyDbFetch($query)['NumGames'];
            } else {
                $numGamesFound += $offset;
            }
        }

        // already did the complex filters. replace with a simple filter on the matching game list
        $whereClause = "AND gd.ID IN ($gameList)";
        $orderBy = '';
        $foundRows = '';
        $listJoin = '';
        $bindings = [];
    }

    $commonFields = 'gd.ID, gd.Title, gd.ImageIcon, gd.TotalTruePoints,
                     COALESCE(gd.achievements_published,0) AS NumAchievements,
                     gd.points_total AS MaxPointsAvailable,
                     CASE WHEN LENGTH(gd.RichPresencePatch) > 0 THEN 1 ELSE 0 END AS RichPresence,
                     CASE WHEN gd.points_total > 0 THEN ROUND(gd.TotalTruePoints/gd.points_total, 2) ELSE 0.00 END AS RetroRatio,
                     gd.ForumTopicID, gd.ConsoleID, c.Name as ConsoleName';

    $games = [];
    if ($dev !== null) {
        $query = "SELECT $foundRows $commonFields,
                         COUNT(*) AS MyAchievements,
                         SUM(ach.Points) AS MyPoints, SUM(ach.TrueRatio) AS MyTrueRatio
                  FROM Achievements ach
                  INNER JOIN GameData gd ON gd.ID = ach.GameID
                  INNER JOIN Console c ON c.ID = gd.ConsoleID $listJoin
                  WHERE ach.Author=:author AND ach.Flags = " . AchievementFlag::OfficialCore . " $whereClause
                  GROUP BY ach.GameID $orderBy";
        foreach (legacyDbFetchAll($query, ['author' => $dev]) as $row) {
            if (!$initialQuery) {
                $gameIds[] = $row['ID'];
            }
            $games[$row['ID']] = [
                'ID' => $row['ID'],
                'Title' => $row['Title'],
                'GameIcon' => $row['ImageIcon'],
                'ConsoleID' => $row['ConsoleID'],
                'ConsoleName' => $row['ConsoleName'],
                'NumAchievements' => $row['NumAchievements'],
                'MaxPointsAvailable' => $row['MaxPointsAvailable'],
                'TotalTruePoints' => $row['TotalTruePoints'],
                'RetroRatio' => $row['RetroRatio'],
                'RichPresence' => $row['RichPresence'],
                'ForumTopicID' => $row['ForumTopicID'],
                'DateModified' => null,
                'MyAchievements' => $row['MyAchievements'],
                'MyPoints' => $row['MyPoints'],
                'MyTrueRatio' => $row['MyTrueRatio'],
                'NotMyAchievements' => $row['NumAchievements'] - $row['MyAchievements'],
                'NumLBs' => null,
                'MyLBs' => null,
            ];
        }
    } else {
        $query = "SELECT $foundRows $commonFields
                  FROM GameData gd
                  INNER JOIN Console c ON c.ID = gd.ConsoleID $listJoin
                  WHERE 1=1 $whereClause
                  GROUP BY gd.ID $orderBy";
        foreach (legacyDbFetchAll($query, $bindings) as $row) {
            if (!$initialQuery) {
                $gameIds[] = $row['ID'];
            }
            $games[$row['ID']] = [
                'ID' => $row['ID'],
                'Title' => $row['Title'],
                'GameIcon' => $row['ImageIcon'],
                'ConsoleID' => $row['ConsoleID'],
                'ConsoleName' => $row['ConsoleName'],
                'NumAchievements' => $row['NumAchievements'],
                'MaxPointsAvailable' => $row['MaxPointsAvailable'],
                'TotalTruePoints' => $row['TotalTruePoints'],
                'RetroRatio' => $row['RetroRatio'],
                'ForumTopicID' => $row['ForumTopicID'],
                'RichPresence' => $row['RichPresence'],
                'DateModified' => null,
                'NumLBs' => null,
            ];
        }
    }

    if (!$initialQuery) {
        if (empty($gameIds)) {
            return 0;
        }
        $gameList = implode(',', $gameIds);

        $numGamesFound = count($gameIds);
        if ($count > 0) {
            if ($numGamesFound == $count) {
                $query = "SELECT FOUND_ROWS() AS NumGames";
                $numGamesFound = legacyDbFetch($query)['NumGames'];
            } else {
                $numGamesFound += $offset;
            }
        }
    }

    // merge leaderboards
    $query = "SELECT GameID, COUNT(*) AS NumLBs
              FROM LeaderboardDef
              WHERE GameID IN ($gameList)
              GROUP BY GameID";
    foreach (legacyDBFetchAll($query) as $row) {
        $games[$row['GameID']]['NumLBs'] = $row['NumLBs'];
    }

    if ($dev !== null) {
        $query = "SELECT GameID, COUNT(*) AS NumLBs
                  FROM LeaderboardDef
                  WHERE GameID IN ($gameList)
                  AND Author = :author
                  GROUP BY GameID";
        foreach (legacyDBFetchAll($query, ['author' => $dev]) as $row) {
            $games[$row['GameID']]['MyLBs'] = $row['NumLBs'];
        }
    }

    // caclulate last updated
    $query = "SELECT GameID, MAX(DateModified) AS DateModified
              FROM Achievements
              WHERE GameID IN ($gameList)
              AND Flags=" . AchievementFlag::OfficialCore . "
              GROUP BY GameID";
    foreach (legacyDbFetchAll($query) as $row) {
        $games[$row['GameID']]['DateModified'] = $row['DateModified'];
    }

    // merge ticket counts
    if ($ticketsFlag) {
        foreach ($games as &$game) {
            $game['OpenTickets'] = 0;
            if ($dev !== null) {
                $game['MyOpenTickets'] = 0;
            }
        }
        if ($dev === null) {
            $query = "SELECT ach.GameID, COUNT(*) AS OpenTickets
                      FROM Ticket tick
                      INNER JOIN Achievements ach ON ach.ID=tick.AchievementID
                      WHERE ach.GameID IN ($gameList)
                      AND tick.ReportState IN (1,3)
                      GROUP BY ach.GameID";
            foreach (legacyDbFetchAll($query) as $row) {
                $games[$row['GameID']]['OpenTickets'] = $row['OpenTickets'];
            }
        } else {
            $query = "SELECT ach.GameID, ach.Author, COUNT(*) AS OpenTickets
                      FROM Ticket tick
                      INNER JOIN Achievements ach ON ach.ID=tick.AchievementID
                      WHERE ach.GameID IN ($gameList)
                      AND tick.ReportState IN (1,3)
                      GROUP BY ach.GameID, ach.Author";
            foreach (legacyDbFetchAll($query) as $row) {
                if ($row['Author'] === $dev) {
                    $games[$row['GameID']]['MyOpenTickets'] += (int) $row['OpenTickets'];
                } else {
                    $games[$row['GameID']]['OpenTickets'] += (int) $row['OpenTickets'];
                }
            }
        }
    }

    foreach ($gameIds as $gameId) {
        $dataOut[] = $games[$gameId];
    }

    return $numGamesFound;
}

function getGamesListData(?int $consoleID = null, bool $officialFlag = false): array
{
    $leftJoinAch = "";
    $whereClause = "";
    if ($officialFlag) {
        $leftJoinAch = "LEFT JOIN Achievements AS ach ON ach.GameID = gd.ID ";
        $whereClause = "WHERE ach.Flags=" . AchievementFlag::OfficialCore . ' ';
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

    $developer = TrimGameMetadata::trimWhitespace($developer);
    $publisher = TrimGameMetadata::trimWhitespace($publisher);
    $genre = TrimGameMetadata::trimWhitespace($genre);
    $released = TrimGameMetadata::trimWhitespace($released);
    $guideURL = TrimGameMetadata::trimWhitespace($guideURL);

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

    sanitize_sql_inputs($developer, $publisher, $genre, $released, $guideURL);

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
    sanitize_sql_inputs($title);
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
    } elseif ($consoleID < 1 || (!isValidConsoleId($consoleID) && $permissions < Permissions::Moderator)) {
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

    sanitize_sql_inputs($dataIn);
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
                WHERE ach.Flags = " . AchievementFlag::OfficialCore . " AND gd.ConsoleID < 100
                AND gd.ID = $gameID
                GROUP BY ach.GameID
                HAVING NumAchievements > 0";

        $dbResult = legacyDbFetch($query);
    } while ($dbResult === null); // game has no achievements or is associated to hub/event console

    return $gameID;
}

function GetPatchData(int $gameID, ?User $user, int $flag): array
{
    $game = Game::find($gameID);
    if (!$game) {
        return [
            'Success' => false,
            'Error' => 'Unknown game',
            'Status' => 404,
            'Code' => 'not_found',
        ];
    }

    $gameData = [
        'ID' => $game->ID,
        'Title' => $game->Title,
        'ImageIcon' => $game->ImageIcon,
        'RichPresencePatch' => $game->RichPresencePatch,
        'ConsoleID' => $game->ConsoleID,
        'ImageIconURL' => media_asset($game->ImageIcon),
        'Achievements' => [],
        'Leaderboards' => [],
    ];

    $achievements = $game->achievements()
        ->orderBy('DisplayOrder') // explicit display order
        ->orderBy('ID');          // tiebreaker on creation sequence

    if ($flag != 0) {
        $achievements = $achievements->where('Flags', '=', $flag);
    }

    $gamePlayers = $game->players_total;
    if ($user) {
        // if the user isn't already tallied in the players for the game,
        // adjust the count now for the rarity calculations.
        $hasPlayerGame = PlayerGame::where('user_id', $user->id)
            ->where('game_id', $game->id)
            ->exists();
        if (!$hasPlayerGame) {
            $gamePlayers++;
        }
    }

    // prevent divide by zero error if the game has never been played before
    $gamePlayers = max(1, $gamePlayers);

    foreach ($achievements->get() as $achievement) {
        if (!AchievementFlag::isValid($achievement->Flags)) {
            continue;
        }

        // calculate rarity assuming it will be used when the player unlocks the achievement,
        // which implies they haven't already unlocked it.
        $rarity = min(100.0, round((float) ($achievement->unlocks_total + 1) * 100 / $gamePlayers, 2));
        $rarityHardcore = min(100.0, round((float) ($achievement->unlocks_hardcore_total + 1) * 100 / $gamePlayers, 2));

        $gameData['Achievements'][] = [
            'ID' => $achievement->ID,
            'MemAddr' => $achievement->MemAddr,
            'Title' => $achievement->Title,
            'Description' => $achievement->Description,
            'Points' => $achievement->Points,
            'Author' => $achievement->Author,
            'Modified' => $achievement->DateModified->unix(),
            'Created' => $achievement->DateCreated->unix(),
            'BadgeName' => $achievement->BadgeName,
            'Flags' => $achievement->Flags,
            'Type' => $achievement->type,
            'Rarity' => $rarity,
            'RarityHardcore' => $rarityHardcore,
            'BadgeURL' => media_asset("Badge/{$achievement->BadgeName}.png"),
            'BadgeLockedURL' => media_asset("Badge/{$achievement->BadgeName}_lock.png"),
        ];
    }

    $leaderboards = $game->leaderboards()
        ->orderBy('DisplayOrder') // explicit display order
        ->orderBy('ID');          // tiebreaker on creation sequence

    foreach ($leaderboards->get() as $leaderboard) {
        $gameData['Leaderboards'][] = [
            'ID' => $leaderboard->ID,
            'Mem' => $leaderboard->Mem,
            'Format' => $leaderboard->Format,
            'LowerIsBetter' => $leaderboard->LowerIsBetter,
            'Title' => $leaderboard->Title,
            'Description' => $leaderboard->Description,
            'Hidden' => ($leaderboard->DisplayOrder < 0),
        ];
    }

    return [
        'Success' => true,
        'PatchData' => $gameData,
    ];
}
