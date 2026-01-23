<?php

use App\Community\Enums\CommentableType;
use App\Models\ForumTopic;
use App\Models\Game;
use App\Models\User;
use App\Platform\Actions\TrimGameMetadataAction;
use App\Platform\Actions\WriteGameSortTitleFromGameTitleAction;
use Illuminate\Support\Facades\DB;

/**
 * @deprecated use Eloquent
 */
function getGameData(int $gameID): ?array
{
    if ($gameID <= 0) {
        return null;
    }

    $game = Game::with('system')->find($gameID);

    return !$game ? null : array_merge($game->toArray(), [
        'ConsoleID' => $game->system_id,
        'ConsoleName' => $game->system->name,
        'NumDistinctPlayers' => $game->players_total ?? 0,

        // Backward-compat keys for legacy code that expects PascalCase column names.
        'Title' => $game->title,
        'ImageIcon' => $game->image_icon_asset_path,
        'ImageTitle' => $game->image_title_asset_path,
        'ImageIngame' => $game->image_ingame_asset_path,
        'ImageBoxArt' => $game->image_box_art_asset_path,
        'ForumTopicID' => $game->forum_topic_id,
        'TotalTruePoints' => $game->points_weighted,
        'RichPresencePatch' => $game->trigger_definition,
        'GuideURL' => $game->legacy_guide_url,
    ]);
}

/**
 * @param-out array $achievementDataOut
 */
function getGameMetadata(
    int $gameID,
    ?User $user,
    ?array &$achievementDataOut,
    ?array &$gameDataOut,
    int $sortBy = 1,
    ?User $user2 = null,
    bool $isPromoted = true,
    bool $metrics = false,
): int {
    $orderBy = match ($sortBy) {
        11 => "ORDER BY ach.order_column DESC, ach.id DESC ",
        2 => "ORDER BY NumAwarded, ach.id ASC ",
        12 => "ORDER BY NumAwarded DESC, ach.id DESC ",
        // 3 and 13 should sort by the date the user unlocked the achievement
        // however, it's not trivial to implement (requires SQL tweaks)
        // 3 => "",
        // 13 => "",
        4 => "ORDER BY ach.points, ach.id ASC ",
        14 => "ORDER BY ach.points DESC, ach.id DESC ",
        5 => "ORDER BY ach.title, ach.id ASC ",
        15 => "ORDER BY ach.title DESC, ach.id DESC ",

        6 => "ORDER BY
            CASE
                WHEN ach.type = 'progression' THEN 0
                WHEN ach.type = 'win_condition' THEN 1
                WHEN ach.type = 'missable' THEN 2
                WHEN ach.type IS NULL THEN 3
                ELSE 4
            END,
            ach.order_column,
            ach.id ASC ",

        16 => "ORDER BY
            CASE
                WHEN ach.type = 'progression' THEN 0
                WHEN ach.type = 'win_condition' THEN 1
                WHEN ach.type = 'missable' THEN 2
                WHEN ach.type IS NULL THEN 3
                ELSE 4
            END DESC,
            ach.order_column DESC,
            ach.id DESC ",

        // 1
        default => "ORDER BY ach.order_column, ach.id ASC ",
    };

    $gameDataOut = getGameData($gameID);

    $achievementDataOut = [];

    if ($gameDataOut == null) {
        return 0;
    }

    $metricsColumns = '';
    if ($metrics) {
        $metricsColumns = 'ach.unlocks_total AS NumAwarded, ach.unlocks_hardcore AS NumAwardedHardcore,';
    }

    $query = "
    SELECT
        ach.id AS ID,
        $metricsColumns
        ach.title AS Title,
        ach.description AS Description,
        ach.points AS Points,
        ach.points_weighted AS TrueRatio,
        COALESCE(ua.display_name, ua.username) AS Author,
        ua.ulid AS AuthorULID,
        ach.modified_at AS DateModified,
        ach.created_at AS DateCreated,
        ach.image_name AS BadgeName,
        ach.order_column AS DisplayOrder,
        ach.trigger_definition AS MemAddr,
        ach.type
    FROM achievements AS ach
    LEFT JOIN users AS ua ON ach.user_id = ua.id
    WHERE ach.game_id = :gameId AND ach.is_promoted = :isPromoted AND ach.deleted_at IS NULL
    $orderBy";

    $achievementDataOut = legacyDbFetchAll($query, [
        'gameId' => $gameID,
        'isPromoted' => $isPromoted,
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
        $userUnlocks = getUserAchievementUnlocksForGame($user, $gameID, $isPromoted);
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
        $friendUnlocks = getUserAchievementUnlocksForGame($user2, $gameID, $isPromoted);
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
    array &$dataOut,
    int $sortBy,
    bool $ticketsFlag = false,
    ?int $filter = 0,
    int $offset = 0,
    int $count = 0,
    ?string $listType = null,
): int {
    $dataOut = [];
    $numGamesFound = 0;

    $gameIds = [];
    $gameList = '';
    $bindings = [];

    if ($sortBy < 10) {
        $titleSort = ifStatement("gd.title LIKE '~%'", 1, 0) . ", gd.title";
    } else {
        $titleSort = ifStatement("gd.title LIKE '~%'", 0, 1) . ", gd.title DESC";
    }

    // Specify 0 for $consoleID to fetch games for all consoles, or an ID for just that console
    $whereClause = '';
    if ($consoleID !== 0) {
        $whereClause = "AND gd.system_id = $consoleID";
    } elseif ($sortBy < 10) {
        $titleSort .= ", s.name ";
    } else {
        $titleSort .= ", s.name DESC";
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
        $listJoin = "INNER JOIN user_game_list_entries ugle ON ugle.game_id = gd.id";
        $whereClause .= " AND ugle.user_id = " . request()->user()->id . " AND ugle.type = :listType";
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
        $query = "SELECT $foundRows gd.id AS ID, gd.points_total AS MaxPointsAvailable, SUM(!ISNULL(lb.id)) AS NumLBs
                  FROM games gd
                  INNER JOIN systems s ON s.id = gd.system_id $listJoin
                  LEFT JOIN leaderboards lb ON lb.game_id = gd.id
                  WHERE 1=1 $whereClause
                  GROUP BY gd.id, gd.points_total $orderBy";
    } elseif ($sortBy === 5 || $sortBy === 15) { // OpenTickets
        $query = "SELECT $foundRows gd.id AS ID, SUM(!ISNULL(tick.id)) AS OpenTickets
                  FROM games gd
                  INNER JOIN systems s ON s.id = gd.system_id $listJoin
                  LEFT JOIN achievements ach ON ach.game_id=gd.id
                  LEFT JOIN tickets tick ON tick.ticketable_id=ach.id AND tick.ticketable_type='achievement' AND tick.state IN ('open','request')
                  WHERE 1=1 $whereClause
                  GROUP BY gd.id $orderBy";
    } elseif ($sortBy === 6 || $sortBy === 16) { // DateModified
        $query = "SELECT $foundRows gd.id AS ID, MAX(ach.modified_at) AS DateModified
                  FROM games gd
                  INNER JOIN systems s ON s.id = gd.system_id $listJoin
                  LEFT JOIN achievements ach ON ach.game_id=gd.id AND ach.is_promoted=1
                  WHERE 1=1 $whereClause
                  GROUP BY gd.id $orderBy";
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
        $whereClause = "AND gd.id IN ($gameList)";
        $orderBy = '';
        $foundRows = '';
        $listJoin = '';
        $bindings = [];
    }

    $commonFields = 'gd.id AS ID, gd.title AS Title, gd.image_icon_asset_path AS ImageIcon, gd.points_weighted AS TotalTruePoints,
                     COALESCE(gd.achievements_published,0) AS NumAchievements,
                     gd.points_total AS MaxPointsAvailable,
                     CASE WHEN LENGTH(gd.trigger_definition) > 0 THEN 1 ELSE 0 END AS RichPresence,
                     CASE WHEN gd.points_total > 0 THEN ROUND(gd.points_weighted/gd.points_total, 2) ELSE 0.00 END AS RetroRatio,
                     gd.forum_topic_id AS ForumTopicID, gd.system_id AS ConsoleID, s.name as ConsoleName';

    $games = [];
    if ($dev !== null) {
        $query = "SELECT $foundRows $commonFields,
                         COUNT(*) AS MyAchievements,
                         SUM(ach.points) AS MyPoints, SUM(ach.points_weighted) AS MyTrueRatio
                  FROM achievements ach
                  INNER JOIN games gd ON gd.id = ach.game_id
                  INNER JOIN systems s ON s.id = gd.system_id $listJoin
                  WHERE ach.user_id = :userId AND ach.is_promoted = 1 $whereClause
                  GROUP BY ach.game_id $orderBy";
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
                  FROM games gd
                  INNER JOIN systems s ON s.id = gd.system_id $listJoin
                  WHERE 1=1 $whereClause
                  GROUP BY gd.id $orderBy";
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
    $query = "SELECT game_id, COUNT(*) AS NumLBs
              FROM leaderboards
              WHERE game_id IN ($gameList)
              GROUP BY game_id";
    foreach (legacyDBFetchAll($query) as $row) {
        $games[$row['game_id']]['NumLBs'] = $row['NumLBs'];
    }

    if ($dev !== null) {
        $query = "SELECT game_id, COUNT(*) AS NumLBs
                  FROM leaderboards
                  WHERE game_id IN ($gameList)
                  AND author_id = :authorId
                  GROUP BY game_id";
        foreach (legacyDBFetchAll($query, ['authorId' => $dev->id]) as $row) {
            $games[$row['game_id']]['MyLBs'] = $row['NumLBs'];
        }
    }

    // calculate last updated
    $query = "SELECT game_id AS GameID, MAX(modified_at) AS DateModified
              FROM achievements
              WHERE game_id IN ($gameList)
              AND is_promoted=1
              GROUP BY game_id";
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
            $query = "SELECT ach.game_id AS GameID, COUNT(*) AS OpenTickets
                      FROM tickets tick
                      INNER JOIN achievements ach ON ach.id=tick.ticketable_id
                      WHERE ach.game_id IN ($gameList)
                      AND tick.ticketable_type = 'achievement'
                      AND tick.state IN ('open','request')
                      GROUP BY ach.game_id";
            foreach (legacyDbFetchAll($query) as $row) {
                $games[$row['GameID']]['OpenTickets'] = $row['OpenTickets'];
            }
        } else {
            $query = "SELECT ach.game_id AS GameID, ua.username as Author, COUNT(*) AS OpenTickets
                      FROM tickets tick
                      INNER JOIN achievements ach ON ach.id = tick.ticketable_id
                      LEFT JOIN users ua ON ach.user_id = ua.id
                      WHERE ach.game_id IN ($gameList)
                      AND tick.ticketable_type = 'achievement'
                      AND tick.state IN ('open','request')
                      GROUP BY ach.game_id, Author";
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
        $leftJoinAch = "LEFT JOIN achievements AS ach ON ach.game_id = gd.id ";
        $whereClause = "WHERE ach.is_promoted=1 ";
    }

    // Specify 0 for $consoleID to fetch games for all consoles, or an ID for just that console
    if (!empty($consoleID)) {
        $whereClause .= $officialFlag ? "AND " : "WHERE ";
        $whereClause .= "system_id=$consoleID ";
    }

    $query = "SELECT DISTINCT gd.title AS Title, gd.id AS ID, gd.system_id AS ConsoleID, gd.image_icon_asset_path AS ImageIcon, s.name as ConsoleName
              FROM games AS gd
              LEFT JOIN systems AS s ON s.id = gd.system_id
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

function getGamesList(?int $consoleID, array &$dataOut, bool $officialFlag = false): int
{
    $dataOut = getGamesListData($consoleID, $officialFlag);

    return count($dataOut);
}

function getGameIDFromTitle(string $gameTitle, int $consoleID): int
{
    sanitize_sql_inputs($gameTitle);

    $query = "SELECT gd.id AS ID
              FROM games AS gd
              WHERE gd.title='$gameTitle' AND gd.system_id='$consoleID'";

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
    if ($game->developer !== $developer) {
        $modifications[] = "developer";
        $game->developer = $developer;
    }
    if ($game->publisher !== $publisher) {
        $modifications[] = "publisher";
        $game->publisher = $publisher;
    }
    if ($game->genre !== $genre) {
        $modifications[] = "genre";
        $game->genre = $genre;
    }
    if ($game->legacy_guide_url !== $guideUrl) {
        $modifications[] = "Guide URL";
        $game->legacy_guide_url = $guideUrl;
    }

    if (count($modifications) == 0) {
        return true;
    }

    $game->save();
    addArticleComment(
        "Server",
        CommentableType::GameModification,
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

        addArticleComment('Server', CommentableType::GameModification, $gameId, "{$user->display_name} changed the game name");
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

    $game->forum_topic_id = $newForumTopicId;
    $game->save();

    addArticleComment('Server', CommentableType::GameModification, $gameId, "{$user->display_name} changed the forum topic");

    return true;
}

function getRichPresencePatch(int $gameId, ?string &$dataOut): bool
{
    $game = Game::find($gameId);
    if (!$game) {
        return false;
    }

    $dataOut = $game->trigger_definition;

    return true;
}
