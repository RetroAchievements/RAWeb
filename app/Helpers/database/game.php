<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\ForumTopic;
use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Actions\ComputeGameSortTitleAction;
use App\Platform\Actions\TrimGameMetadata;
use App\Platform\Actions\UpdateGameSetFromGameAlternativesModification;
use App\Platform\Actions\WriteGameSortTitleFromGameTitleAction;
use App\Platform\Enums\AchievementFlag;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Facades\CauserResolver;

function getGameData(int $gameID): ?array
{
    if ($gameID <= 0) {
        return null;
    }

    $game = Game::with('system')->find($gameID);

    return !$game ? null : array_merge($game->toArray(), [
        'ConsoleID' => $game->system->ID,
        'ConsoleName' => $game->system->Name,
        'NumDistinctPlayers' => $game->players_total,
    ]);
}

// If the game is a subset, identify its parent game.
function getParentGameFromGameTitle(string $title, int $consoleId): ?Game
{
    if (mb_strpos($title, '[Subset') !== false) {
        $foundGame = Game::where('Title', $title)->where('ConsoleID', $consoleId)->first();

        return $foundGame->getParentGame() ?? null;
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
        $parentGame = getParentGameFromGameTitle($gameDataOut['Title'], $gameDataOut['ConsoleID']);
        $numDistinctPlayersSelector = $parentGame?->players_total ?: getGameData($gameID)['NumDistinctPlayers'];
        $gameDataOut['ParentGameID'] = $parentGame?->id;
        $gameDataOut['NumDistinctPlayers'] = $numDistinctPlayersSelector ?? 0;

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
        ua.User AS Author,
        ach.DateModified,
        ach.DateCreated,
        ach.BadgeName,
        ach.DisplayOrder,
        ach.MemAddr,
        ach.type
    FROM Achievements AS ach
    LEFT JOIN UserAccounts AS ua ON ach.user_id = ua.ID
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
    ?User $dev,
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
                  WHERE ach.user_id = :userId AND ach.Flags = " . AchievementFlag::OfficialCore . " $whereClause
                  GROUP BY ach.GameID $orderBy";
        foreach (legacyDbFetchAll($query, ['userId' => $dev->id]) as $row) {
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
                  AND author_id = :authorId
                  GROUP BY GameID";
        foreach (legacyDBFetchAll($query, ['authorId' => $dev->id]) as $row) {
            $games[$row['GameID']]['MyLBs'] = $row['NumLBs'];
        }
    }

    // calculate last updated
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
            $query = "SELECT ach.GameID, ua.User as Author, COUNT(*) AS OpenTickets
                      FROM Ticket tick
                      INNER JOIN Achievements ach ON ach.ID = tick.AchievementID
                      LEFT JOIN UserAccounts ua ON ach.user_id = ua.ID
                      WHERE ach.GameID IN ($gameList)
                      AND tick.ReportState IN (1,3)
                      GROUP BY ach.GameID, Author";
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

function getGamesListDataNamesOnly(int $consoleId, bool $officialFlag = false): array
{
    return Game::join('Console', 'GameData.ConsoleID', '=', 'Console.ID')
        ->when($consoleId !== 0, function ($query) use ($consoleId) {
            return $query->where('GameData.ConsoleID', '=', $consoleId);
        })
        ->when($officialFlag === true, function ($query) {
            return $query->where('GameData.achievements_published', '>', 0);
        })
        ->orderBy('Console.Name')
        ->orderBy('GameData.Title')
        ->select('GameData.Title', 'GameData.ID')
        ->pluck('GameData.Title', 'GameData.ID') // return mapping of ID => Title
        ->toArray();
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
    string $username,
    int $gameId,
    ?string $developer,
    ?string $publisher,
    ?string $genre,
    ?string $guideUrl,
): bool {
    $game = Game::with("system")->find($gameId);
    if (!$game) {
        return false;
    }

    $developer = TrimGameMetadata::trimWhitespace($developer);
    $publisher = TrimGameMetadata::trimWhitespace($publisher);
    $genre = TrimGameMetadata::trimWhitespace($genre);
    $guideUrl = TrimGameMetadata::trimWhitespace($guideUrl);

    $modifications = [];
    if ($game->Developer !== $developer) {
        $modifications[] = "developer";
        $game->Developer = $developer;
    }
    if ($game->Publisher !== $publisher) {
        $modifications[] = "publisher";
        $game->Publisher = $publisher;
    }
    if ($game->Genre !== $genre) {
        $modifications[] = "genre";
        $game->Genre = $genre;
    }
    if ($game->GuideURL !== $guideUrl) {
        $modifications[] = "Guide URL";
        $game->GuideURL = $guideUrl;
    }

    if (count($modifications) == 0) {
        return true;
    }

    $game->save();
    addArticleComment(
        "Server",
        ArticleType::GameModification,
        $gameId,
        "{$username} changed the " .
            implode(", ", $modifications) .
            (count($modifications) == 1 ? " field" : " fields"),
    );

    return true;
}

function modifyGameTitle(string $username, int $gameId, string $value): bool
{
    if (mb_strlen($value) < 2) {
        return false;
    }

    $game = Game::find($gameId);
    if (!$game) {
        return false;
    }

    $originalTitle = $game->title;
    $game->title = $value;

    $newSortTitle = (new WriteGameSortTitleFromGameTitleAction())->execute(
        $game,
        $originalTitle,
        shouldSaveGame: false,
    );

    if ($newSortTitle !== null) {
        $game->sort_title = $newSortTitle;
    }

    if ($game->isDirty()) {
        $game->save();
        addArticleComment('Server', ArticleType::GameModification, $gameId, "{$username} changed the game name");
    }

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

            // Double writes to game_sets.
            foreach ($ids as $childId) {
                (new UpdateGameSetFromGameAlternativesModification())->execute($gameID, $childId);
            }
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

            // Double writes to game_sets.
            foreach ($ids as $childId) {
                (new UpdateGameSetFromGameAlternativesModification())->execute($gameID, $childId, isAttaching: false);
            }
        }
    }
}

function modifyGameForumTopic(string $username, int $gameId, int $newForumTopicId): bool
{
    if ($gameId == 0 || $newForumTopicId == 0) {
        return false;
    }

    if (!ForumTopic::where('ID', $newForumTopicId)->exists()) {
        return false;
    }

    $game = Game::find($gameId);
    if (!$game) {
        return false;
    }

    $game->ForumTopicID = $newForumTopicId;
    $game->save();

    addArticleComment('Server', ArticleType::GameModification, $gameId, "{$username} changed the forum topic");

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

function createNewGame(string $title, int $systemId): ?array
{
    try {
        $game = new Game();
        $game->Title = $title;
        $game->sort_title = (new ComputeGameSortTitleAction())->execute($title);
        $game->ConsoleID = $systemId;
        $game->ForumTopicID = null;
        $game->Flags = 0;
        $game->ImageIcon = '/Images/000001.png';
        $game->ImageTitle = '/Images/000002.png';
        $game->ImageIngame = '/Images/000002.png';
        $game->ImageBoxArt = '/Images/000002.png';
        $game->Publisher = null;
        $game->Developer = null;
        $game->Genre = null;
        $game->Released = null;
        $game->IsFinal = 0;
        $game->RichPresencePatch = null;
        $game->TotalTruePoints = 0;

        $game->save();

        return [
            'ID' => $game->id,
            'Title' => $title,
        ];
    } catch (Exception $e) {
        Log::error('Failed to create new game', ['error' => $e->getMessage()]);

        return null;
    }
}

function submitNewGameTitleJSON(
    string $username,
    string $md5,
    ?int $gameIDin,
    string $titleIn,
    int $consoleID,
    ?string $description
): array {
    $unsanitizedDescription = $description;
    sanitize_sql_inputs($username, $md5, $description);

    $retVal = [];
    $retVal['MD5'] = $md5;
    $retVal['ConsoleID'] = $consoleID;
    $retVal['GameID'] = $gameIDin;
    $retVal['GameTitle'] = $titleIn;
    $retVal['Success'] = true;

    $userModel = User::where('User', $username)->first();
    $permissions = (int) $userModel->getAttribute('Permissions');
    $userId = $userModel->id;
    CauserResolver::setCauser($userModel);

    if (!isset($username)) {
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
            $query = "INSERT INTO game_hashes (md5, game_id, user_id, name) VALUES( '$md5', '$gameID', '$userId', ";
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
                 * $username added $md5, $gameID to game_hashes, and $gameID, $titleIn to GameData
                 */

                // Log hash linked
                if (!empty($unsanitizedDescription)) {
                    addArticleComment("Server", ArticleType::GameHash, $gameID, $md5 . " linked by " . $username . ". Description: \"" . $unsanitizedDescription . "\"");
                } else {
                    addArticleComment("Server", ArticleType::GameHash, $gameID, $md5 . " linked by " . $username);
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

function modifyGameRichPresence(string $username, int $gameId, string $dataIn): bool
{
    getRichPresencePatch($gameId, $existingData);
    if ($existingData == $dataIn) {
        return true;
    }

    $game = Game::find($gameId);
    if (!$game) {
        return false;
    }

    $game->RichPresencePatch = $dataIn;
    $game->save();

    addArticleComment('Server', ArticleType::GameModification, $gameId, "{$username} changed the rich presence script");

    return true;
}

function getRichPresencePatch(int $gameId, ?string &$dataOut): bool
{
    $game = Game::find($gameId);
    if (!$game) {
        return false;
    }

    $dataOut = $game->RichPresencePatch;

    return true;
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

    foreach ($achievements->with('developer')->get() as $achievement) {
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
            'Author' => $achievement->developer->User ?? '',
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
