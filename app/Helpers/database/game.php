<?php

use App\Models\Achievement;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\User;
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
        $rows = collect(DB::select($query, $bindings))->map(fn ($row) => (array) $row);
        foreach ($rows as $row) {
            $gameIds[] = $row['ID'];
        }

        if (empty($gameIds)) {
            return 0;
        }
        $gameList = implode(',', $gameIds);

        $numGamesFound = count($gameIds);
        if ($count > 0) {
            if ($numGamesFound == $count) {
                $foundRowsResult = DB::select("SELECT FOUND_ROWS() AS NumGames");
                $numGamesFound = ((array) $foundRowsResult[0])['NumGames'];
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
        $rows = collect(DB::select($query, ['userId' => $dev->id]))->map(fn ($row) => (array) $row);
        foreach ($rows as $row) {
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
        $rows = collect(DB::select($query, $bindings))->map(fn ($row) => (array) $row);
        foreach ($rows as $row) {
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
                $foundRowsResult = DB::select("SELECT FOUND_ROWS() AS NumGames");
                $numGamesFound = ((array) $foundRowsResult[0])['NumGames'];
            } else {
                $numGamesFound += $offset;
            }
        }
    }

    // merge leaderboards
    $leaderboardCounts = Leaderboard::query()
        ->withTrashed()
        ->whereIn('game_id', $gameIds)
        ->selectRaw('game_id, COUNT(*) AS NumLBs')
        ->groupBy('game_id')
        ->toBase()
        ->get()
        ->map(fn ($row) => (array) $row);
    foreach ($leaderboardCounts as $row) {
        $games[$row['game_id']]['NumLBs'] = $row['NumLBs'];
    }

    if ($dev !== null) {
        $myLeaderboardCounts = Leaderboard::query()
            ->withTrashed()
            ->whereIn('game_id', $gameIds)
            ->where('author_id', $dev->id)
            ->selectRaw('game_id, COUNT(*) AS NumLBs')
            ->groupBy('game_id')
            ->toBase()
            ->get()
            ->map(fn ($row) => (array) $row);
        foreach ($myLeaderboardCounts as $row) {
            $games[$row['game_id']]['MyLBs'] = $row['NumLBs'];
        }
    }

    // calculate last updated
    $dateModifiedRows = Achievement::query()
        ->withTrashed()
        ->whereIn('game_id', $gameIds)
        ->where('is_promoted', 1)
        ->selectRaw('game_id AS GameID, MAX(modified_at) AS DateModified')
        ->groupBy('game_id')
        ->toBase()
        ->get()
        ->map(fn ($row) => (array) $row);
    foreach ($dateModifiedRows as $row) {
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
            $openTicketRows = DB::table('tickets as tick')
                ->join('achievements as ach', 'ach.id', '=', 'tick.ticketable_id')
                ->whereIn('ach.game_id', $gameIds)
                ->where('tick.ticketable_type', 'achievement')
                ->whereIn('tick.state', ['open', 'request'])
                ->selectRaw('ach.game_id AS GameID, COUNT(*) AS OpenTickets')
                ->groupBy('ach.game_id')
                ->get()
                ->map(fn ($row) => (array) $row);
            foreach ($openTicketRows as $row) {
                $games[$row['GameID']]['OpenTickets'] = $row['OpenTickets'];
            }
        } else {
            $openTicketRows = DB::table('tickets as tick')
                ->join('achievements as ach', 'ach.id', '=', 'tick.ticketable_id')
                ->leftJoin('users as ua', 'ach.user_id', '=', 'ua.id')
                ->whereIn('ach.game_id', $gameIds)
                ->where('tick.ticketable_type', 'achievement')
                ->whereIn('tick.state', ['open', 'request'])
                ->selectRaw('ach.game_id AS GameID, ua.username as Author, COUNT(*) AS OpenTickets')
                ->groupBy('ach.game_id', 'Author')
                ->get()
                ->map(fn ($row) => (array) $row);
            foreach ($openTicketRows as $row) {
                if ($row['Author'] === $dev->username) {
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

function getGameIDFromTitle(string $gameTitle, int $consoleID): int
{
    $gameId = Game::query()
        ->withoutGlobalScopes()
        ->where('title', $gameTitle)
        ->where('system_id', $consoleID)
        ->value('id');

    return (int) ($gameId ?? 0);
}
