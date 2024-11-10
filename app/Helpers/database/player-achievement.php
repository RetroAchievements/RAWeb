<?php

use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\PlayerAchievement;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use Illuminate\Support\Collection;

/**
 * @deprecated see UnlockPlayerAchievementAction
 */
function unlockAchievement(User $user, int $achievementId, bool $isHardcore, ?GameHash $gameHash = null): array
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

    if ($playerGame && $gameHash) {
        $playerGame->game_hash_id = $gameHash->id;
    }

    if (!$alreadyAwarded) {
        // The client is expecting to receive the number of AchievementsRemaining in the response, and if
        // it's 0, a mastery placard will be shown. Multiple achievements may be unlocked by the client at
        // the same time using separate requests, so we need to update the unlock counts for the
        // player_game (and commit it) as soon as possible so whichever request is processed last _should_
        // return the correct number of remaining achievements. It will be accurately recalculated by the
        // UpdatePlayerGameMetricsAction triggered by an asynchronous UnlockPlayerAchievementJob.
        // Also update user points for the response, but don't immediately commit them to avoid unnecessary
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
        if ($isHardcore && $achievement->eventAchievements()->active()->exists()) {
            // if event achievements are active, assume they still need to be unlocked and indicate
            // success. this allows dorequest to forward the unlocks for the event achievements.
            $retVal['Success'] = true;
        }

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
    int $isHardcore,
    ?string $requestedBy = null,
    int $flag = AchievementFlag::OfficialCore,
    int $numPlayers = 0
): array {
    /** @var Game $game */
    $game = Game::withCount(['achievements' => fn ($query) => $query->flag($flag)])->find($gameID);
    $results = null;

    if (!$game || !$game->achievements_count) {
        // NOTE this will return an empty array instead of an empty object. keep it like this for backwards compatibility.
        return [];
    }

    // if a game has more than 100 players, don't filter out the untracked users as the
    // join becomes very expensive. will be addressed when denormalized data is captured
    $shouldJoinUsers = $numPlayers < 100;

    // Returns an array of the number of players who have achieved each total, up to the max.
    if ($flag === AchievementFlag::OfficialCore) {
        $countColumn = $isHardcore ? 'player_games.achievements_unlocked_hardcore' : 'player_games.achievements_unlocked_softcore';

        $countQuery = DB::table("player_games")
            ->selectRaw("$countColumn as AwardedCount, count(*) as NumUniquePlayers")
            ->where("$countColumn", ">", 0)
            ->where('player_games.game_id', $gameID)
            ->groupBy('AwardedCount')
            ->orderByDesc('AwardedCount');

        if ($shouldJoinUsers) {
            $countQuery->join("UserAccounts", "player_games.user_id", "=", "UserAccounts.ID")
                ->where(fn ($query) => $query
                    ->where("UserAccounts.Untracked", 0)
                    ->orWhere("UserAccounts.User", $requestedBy)
            );
        }

        $results = $countQuery->get();
    } else {
        $countColumn = $isHardcore ? 'sub.hardcore_unlocks' : 'sub.softcore_unlocks';

        $subQuery = PlayerAchievement::query()
            ->selectRaw(
                "player_achievements.user_id, 
                sum(case when player_achievements.unlocked_hardcore_at is null then 1 else 0 end) as softcore_unlocks, 
                sum(case when player_achievements.unlocked_hardcore_at is not null then 1 else 0 end) as hardcore_unlocks"
            )
            ->join("Achievements", "player_achievements.achievement_id", "=", "Achievements.ID")
            ->where("Achievements.GameID", $gameID)
            ->where("Achievements.Flags", AchievementFlag::Unofficial)
            ->groupBy("player_achievements.user_id");

        if ($shouldJoinUsers) {
            $subQuery->join("UserAccounts", "player_achievements.user_id", "=", "UserAccounts.ID")
                ->where(fn ($query) => $query
                    ->where("UserAccounts.Untracked", 0)
                    ->orWhere("UserAccounts.User", $requestedBy)
            );
        }

        $countQuery = PlayerAchievement::query()
            ->fromSub($subQuery, "sub")
            ->selectRaw("$countColumn as AwardedCount, count(*) as NumUniquePlayers")
            ->where("$countColumn", ">", 0)
            ->groupBy("$countColumn")
            ->orderBy("$countColumn", "desc");

        $results = $countQuery->get();
    }

    $awardedCounts = $results->pluck('NumUniquePlayers', 'AwardedCount')->toArray();

    return collect()
        ->range(1, $game->achievements_count)
        ->flip()
        ->map(fn ($value, $index) => $awardedCounts[$index] ?? 0)
        ->sortKeys()
        ->toArray();
}
