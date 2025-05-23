<?php

use App\Community\Enums\ArticleType;
use App\Enums\GameHashCompatibility;
use App\Enums\Permissions;
use App\Models\ForumTopic;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\User;
use App\Platform\Actions\TrimGameMetadataAction;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlagsAction;
use App\Platform\Actions\UpsertTriggerVersionAction;
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

function getGameMetadata(
    int $gameID,
    ?User $user,
    ?array &$achievementDataOut,
    ?array &$gameDataOut,
    int $sortBy = 1,
    ?User $user2 = null,
    AchievementFlag $flag = AchievementFlag::OfficialCore,
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
    if ($metrics) {
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
        COALESCE(ua.display_name, ua.User) AS Author,
        ua.ulid AS AuthorULID,
        ach.DateModified,
        ach.DateCreated,
        ach.BadgeName,
        ach.DisplayOrder,
        ach.MemAddr,
        ach.type
    FROM Achievements AS ach
    LEFT JOIN UserAccounts AS ua ON ach.user_id = ua.ID
    WHERE ach.GameID = :gameId AND ach.Flags = :achievementFlag AND ach.deleted_at IS NULL
    $orderBy";

    $achievementDataOut = legacyDbFetchAll($query, [
        'gameId' => $gameID,
        'achievementFlag' => $flag->value,
    ])
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
                  LEFT JOIN Achievements ach ON ach.GameID=gd.ID AND ach.Flags=" . AchievementFlag::OfficialCore->value . "
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
                  WHERE ach.user_id = :userId AND ach.Flags = " . AchievementFlag::OfficialCore->value . " $whereClause
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
              AND Flags=" . AchievementFlag::OfficialCore->value . "
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
        $whereClause = "WHERE ach.Flags=" . AchievementFlag::OfficialCore->value . ' ';
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
    User $user,
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

    // FIXME actions should only expose `execute()`
    $developer = TrimGameMetadataAction::trimWhitespace($developer);
    $publisher = TrimGameMetadataAction::trimWhitespace($publisher);
    $genre = TrimGameMetadataAction::trimWhitespace($genre);
    $guideUrl = TrimGameMetadataAction::trimWhitespace($guideUrl);

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
        "{$user->display_name} changed the " .
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

    $user = User::whereName($username)->first();
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

        // Update the canonical title in game_releases.
        $canonicalTitle = $game->releases()->where('is_canonical_game_title', true)->first();
        if ($canonicalTitle) {
            $canonicalTitle->title = $value;
            $canonicalTitle->save();
        }

        addArticleComment('Server', ArticleType::GameModification, $gameId, "{$user->display_name} changed the game name");
    }

    return true;
}

function modifyGameForumTopic(string $username, int $gameId, int $newForumTopicId): bool
{
    if ($gameId == 0 || $newForumTopicId == 0) {
        return false;
    }

    if (!ForumTopic::where('id', $newForumTopicId)->exists()) {
        return false;
    }

    $user = User::whereName($username)->first();
    $game = Game::find($gameId);
    if (!$game) {
        return false;
    }

    $game->ForumTopicID = $newForumTopicId;
    $game->save();

    addArticleComment('Server', ArticleType::GameModification, $gameId, "{$user->display_name} changed the forum topic");

    return true;
}

function submitNewGameTitleJSON(
    string $username,
    string $md5,
    ?int $gameIDin,
    string $titleIn,
    int $consoleID,
    ?string $description
): array {
    $retVal = [];

    $user = User::whereName($username)->first();
    if (!$user) {
        $retVal['Error'] = "Unknown user";
        $retVal['Success'] = false;
    }

    $permissions = (int) $user->getAttribute('Permissions');
    CauserResolver::setCauser($user);

    if ($permissions < Permissions::Developer) {
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
            $game = Game::find($gameIDin);
        }
        if (empty($game)) {
            $game = Game::where('title', $titleIn)->where('ConsoleID', $consoleID)->first();
        }
        if (!$game) {
            // new game
            $game = new Game();
            $game->Title = $titleIn;
            $game->ConsoleID = $consoleID;
            $game->ForumTopicID = null;
            $game->Flags = 0;
            $game->ImageIcon = '/Images/000001.png';
            $game->ImageTitle = '/Images/000002.png';
            $game->ImageIngame = '/Images/000002.png';
            $game->ImageBoxArt = '/Images/000002.png';
            $game->Publisher = null;
            $game->Developer = null;
            $game->Genre = null;
            $game->RichPresencePatch = null;
            $game->TotalTruePoints = 0;

            $game->save();

            // Create the initial canonical title in game_releases.
            $game->releases()->create([
                'title' => $titleIn,
                'is_canonical_game_title' => true,
            ]);

            // Create an empty GameAchievementSet and AchievementSet.
            (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);
        }

        $retVal['Success'] = true;
        $retVal['GameID'] = $game->id;

        if (!GameHash::where('game_id', $game->id)->where('md5', $md5)->exists()) {
            // associate md5 to game
            $gameHash = new GameHash([
                'game_id' => $game->id,
                'user_id' => $user->id,
                'md5' => $md5,
                'compatibility' => GameHashCompatibility::Compatible,
            ]);
            if (!empty($description)) {
                $gameHash->name = $description;
            }
            $gameHash->save();

            // log hash linked
            $message = "$md5 linked by {$user->display_name}.";
            if (!empty($description)) {
                $message .= " Description: \"$description\"";
            }
            addArticleComment("Server", ArticleType::GameHash, $game->id, $message);
        }
    }

    return $retVal;
}

function modifyGameRichPresence(User $user, int $gameId, string $dataIn): bool
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

    (new UpsertTriggerVersionAction())->execute(
        $game,
        $dataIn,
        versioned: true, // rich presence is always published
        user: $user
    );

    addArticleComment('Server', ArticleType::GameModification, $gameId, "{$user->display_name} changed the rich presence script");

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
