<?php

use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * @deprecated see UnlockPlayerAchievementAction
 */
function unlockAchievement(User $user, int $achievementId, bool $isHardcore): array
{
    $retVal = [
        'Success' => false,
    ];

    $achievement = Achievement::find($achievementId);
    if (!$achievement) {
        $retVal['Error'] = "Data not found for achievement $achievementId";

        return $retVal;
    }

    if ($achievement->Flags === AchievementFlag::Unofficial) { // do not award Unofficial achievements
        $retVal['Error'] = "Unofficial achievements cannot be unlocked";

        return $retVal;
    }

    $hasRegular = false;
    $hasHardcore = false;
    $playerAchievement = PlayerAchievement::where('user_id', $user->ID)
        ->where('achievement_id', $achievementId)
        ->first();
    if ($playerAchievement) {
        $hasRegular = ($playerAchievement->unlocked_at != null);
        $hasHardcore = ($playerAchievement->unlocked_hardcore_at != null);
    }
    $alreadyAwarded = $isHardcore ? $hasHardcore : $hasRegular;

    $playerGame = PlayerGame::where('user_id', $user->id)
        ->where('game_id', $achievement->GameID)
        ->first();

    if (!$alreadyAwarded) {
        $now = Carbon::now();

        // The client is expecting to receive the number of AchievementsRemaining in the response, and if
        // it's 0, a mastery placard will be shown. Multiple achievements may be unlocked by the client at
        // the same time using separate requests, so we need to update the unlock counts for the
        // player_game (and commit it) as soon as possible so whichever reqeust is processed last _should_
        // return the correct number of remaining achievements. It will be accurately recalculated by the
        // UpdatePlayerGameMetrics action triggered by an asynchronous UnlockPlayerAchievementJob.
        // Also update user points for the response, but don't immediately commit them to avoid uncessary
        // DB writes.
        if ($isHardcore && !$hasHardcore) {
            if ($playerGame) {
                $playerGame->achievements_unlocked_hardcore++;
                $user->RAPoints += $achievement->Points;

                if ($hasRegular) {
                    $playerGame->achievements_unlocked--;
                    $user->RASoftcorePoints -= $achievement->Points;
                }

                $playerGame->save();
            }
        } elseif (!$isHardcore && !$hasRegular) {
            $user->RASoftcorePoints += $achievement->Points;

            if ($playerGame) {
                $playerGame->achievements_unlocked++;
                $playerGame->save();
            }
        }
    }

    if ($playerGame) {
        if ($isHardcore) {
            $retVal['AchievementsRemaining'] = $achievement->game->achievements_published - $playerGame->achievements_unlocked_hardcore;
        } else {
            $retVal['AchievementsRemaining'] = $achievement->game->achievements_published - $playerGame->achievements_unlocked;
        }
    } else {
        $retVal['AchievementsRemaining'] = $achievement->game->achievements_published - 1;
    }

    if ($alreadyAwarded) {
        // =============================================================================
        // ===== DO NOT CHANGE THESE MESSAGES ==========================================
        // The client detects the "User already has" and does not report them as errors.
        if ($isHardcore) {
            $retVal['Error'] = "User already has this achievement unlocked in hardcore mode.";
        } else {
            $retVal['Error'] = "User already has this achievement unlocked.";
        }
        // =============================================================================

        return $retVal;
    }

    $retVal['Success'] = true;
    // Achievements all awarded. Now housekeeping (no error handling?)

    static_setlastearnedachievement($achievement->ID, $user->User, $achievement->Points);

    return $retVal;
}

/**
 * @return Collection<int, mixed>
 */
function getAchievementUnlocksData(
    int $achievementId,
    ?string $username,
    ?int &$numWinners,
    ?int &$numWinnersHardcore,
    ?int &$numPossibleWinners,
    ?int $parentGameId = null,
    int $offset = 0,
    int $limit = 50
): Collection {

    $achievement = Achievement::firstWhere('ID', $achievementId);
    if (!$achievement) {
        return new Collection();
    }

    $numWinners = $achievement->unlocks_total ?? 0;
    $numWinnersHardcore = $achievement->unlocks_hardcore_total ?? 0;
    $numPossibleWinners = $achievement->game->players_total ?? 0;

    // Get recent winners, and their most recent activity
    return PlayerAchievement::where('achievement_id', $achievementId)
        ->join('UserAccounts', 'UserAccounts.ID', '=', 'user_id')
        ->orderByRaw('COALESCE(unlocked_hardcore_at, unlocked_at) DESC')
        ->select(['UserAccounts.User', 'UserAccounts.RAPoints', 'UserAccounts.RASoftcorePoints', 'unlocked_at', 'unlocked_hardcore_at'])
        ->offset($offset)
        ->limit($limit)
        ->get()
        ->map(function ($row) {
            return [
                'User' => $row->User,
                'RAPoints' => $row->RAPoints,
                'RASoftcorePoints' => $row->RASoftcorePoints,
                'DateAwarded' => $row->unlocked_hardcore_at ?? $row->unlocked_at,
                'HardcoreMode' => $row->unlocked_hardcore_at ? 1 : 0,
            ];
        });
}

function getRecentUnlocksPlayersData(
    int $achID,
    int $offset,
    int $count,
    ?string $user = null,
    bool $friendsOnly = false
): array {
    $retVal = [
        'NumEarned' => 0,
        'GameID' => 0,
        'TotalPlayers' => 0,
        'RecentWinner' => [],
    ];

    $achievement = Achievement::find($achID);
    if (!$achievement) {
        return $retVal;
    }

    $game = Game::find($achievement->GameID);
    if (!$game) {
        return $retVal;
    }
    $retVal['GameID'] = $game->ID;

    $retVal['NumEarned'] = $achievement->unlocks_total;
    $retVal['TotalPlayers'] = $game->players_total;

    $extraWhere = "";
    if ($friendsOnly && isset($user) && $user) {
        $friendSubquery = GetFriendsSubquery($user, false);
        $extraWhere = " AND u.User IN ( $friendSubquery ) ";
    }

    // Get recent winners, and their most recent activity:
    $query = "SELECT u.User, u.RAPoints, " . unixTimestampStatement('pa.unlocked_at', 'DateAwarded') . "
              FROM player_achievements AS pa
              LEFT JOIN UserAccounts AS u ON u.ID = pa.user_id
              WHERE pa.achievement_id = $achID $extraWhere
              ORDER BY pa.unlocked_at DESC
              LIMIT $offset, $count";

    foreach (legacyDbFetchAll($query) as $db_entry) {
        $db_entry['RAPoints'] = (int) $db_entry['RAPoints'];
        $db_entry['DateAwarded'] = (int) $db_entry['DateAwarded'];
        $retVal['RecentWinner'][] = $db_entry;
    }

    return $retVal;
}

/**
 * Gets the number of softcore and hardcore awards for an achievement since a given time.
 */
function getUnlocksSince(int $id, string $date): array
{
    $softcoreCount = PlayerAchievement::where('achievement_id', $id)
        ->where('unlocked_at', '>', $date)->count();
    $hardcoreCount = PlayerAchievement::where('achievement_id', $id)
        ->where('unlocked_hardcore_at', '>', $date)->count();

    return [
        'softcoreCount' => $softcoreCount,
        'hardcoreCount' => $hardcoreCount,
    ];
}

/**
 * Gets a list of users who have unlocked an achievement or list of achievements within a given time-range.
 */
function getUnlocksInDateRange(array $achievementIDs, string $startTime, string $endTime, int $hardcoreMode): array
{
    if (empty($achievementIDs)) {
        return [];
    }

    $column = $hardcoreMode ? 'unlocked_hardcore_at' : 'unlocked_at';

    $dateQuery = "";
    if (strtotime($startTime)) {
        if (strtotime($endTime)) {
            // valid start and end
            $dateQuery = "AND pa.$column BETWEEN '$startTime' AND '$endTime'";
        } else {
            // valid start, invalid end
            $dateQuery = "AND pa.$column >= '$startTime'";
        }
    } else {
        if (strtotime($endTime)) {
            // invalid start, valid end
            $dateQuery = "AND pa.$column <= '$endTime'";
        } else {
            $dateQuery = "AND pa.$column IS NOT NULL";
        }
    }

    $userArray = [];
    foreach ($achievementIDs as $nextID) {
        $query = "SELECT ua.User
                      FROM player_achievements AS pa
                      INNER JOIN UserAccounts AS ua ON ua.ID = pa.user_id
                      WHERE pa.achievement_id = $nextID
                      AND ua.Untracked = 0
                      $dateQuery
                      ORDER BY ua.User";
        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            while ($db_entry = mysqli_fetch_assoc($dbResult)) {
                $userArray[$nextID][] = $db_entry['User'];
            }
        }
    }

    return $userArray;
}

/**
 * Gets the achievement distribution to display on the game page.
 */
function getAchievementDistribution(
    int $gameID,
    int $hardcore,
    ?string $requestedBy = null,
    int $flag = AchievementFlag::OfficialCore,
    int $numPlayers = 0
): array {
    /** @var Game $game */
    $game = Game::withCount(['achievements' => fn ($query) => $query->flag($flag)])
        ->find($gameID);

    if (!$game || !$game->achievements_count) {
        // NOTE this will return an empty array instead of an empty object. keep it like this for backwards compatibility.
        return [];
    }

    $bindings = [
        'gameId' => $gameID,
    ];

    // if a game has more than 100 players, don't filter out the untracked users as the
    // join becomes very expensive. will be addressed when denormalized data is captured
    $joinStatement = '';
    $joinStatementUnofficial = '';
    $requestedByStatement = '';
    if ($numPlayers < 100) {
        $joinStatement = 'INNER JOIN UserAccounts AS ua ON ua.ID = pg.user_id';
        $joinStatementUnofficial = 'INNER JOIN UserAccounts AS ua ON ua.ID = pa.user_id';
        $requestedByStatement = 'AND (ua.Untracked = 0';
        if ($requestedBy) {
            $bindings['requestedBy'] = $requestedBy;
            $requestedByStatement .= ' OR ua.User = :requestedBy';
        }
        $requestedByStatement .= ')';
    }

    // Returns an array of the number of players who have achieved each total, up to the max.
    if ($flag === AchievementFlag::OfficialCore) {
        $countColumn = $hardcore ? 'achievements_unlocked_hardcore' : 'achievements_unlocked';
        $query = "SELECT pg.$countColumn AS AwardedCount, COUNT(*) AS NumUniquePlayers
                    FROM player_games AS pg
                    $joinStatement
                    WHERE pg.game_id = :gameId AND pg.$countColumn > 0
                    $requestedByStatement
                    GROUP BY AwardedCount
                    ORDER BY AwardedCount DESC";
    } else {
        $hardcoreStatement = $hardcore ? 'AND pa.unlocked_hardcore_at IS NOT NULL' : '';
        $query = "SELECT InnerTable.AwardedCount AS AwardedCount, COUNT(*) AS NumUniquePlayers
                FROM (
                    SELECT COUNT(*) AS AwardedCount
                    FROM player_achievements AS pa
                    INNER JOIN Achievements AS ach ON ach.ID = pa.achievement_id
                    $joinStatementUnofficial
                    WHERE ach.GameID = :gameId
                    $hardcoreStatement
                    AND ach.Flags = $flag
                    $requestedByStatement
                    GROUP BY pa.user_id
                    ORDER BY AwardedCount DESC
                ) AS InnerTable
                GROUP BY InnerTable.AwardedCount";
    }

    $data = legacyDbFetchAll($query, $bindings)
        ->mapWithKeys(fn ($distribution) => [(int) $distribution['AwardedCount'] => (int) $distribution['NumUniquePlayers']]);

    return collect()->range(1, $game->achievements_count)
        ->flip()
        ->map(fn ($value, $index) => $data->get($index, 0))
        ->sortKeys()
        ->toArray();
}
