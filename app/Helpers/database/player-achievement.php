<?php

use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerAchievement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @return Collection<int, mixed>
 */
function getAchievementUnlocksData(
    int $achievementId,
    ?string $username,
    ?int &$numWinners,
    ?int &$numWinnersHardcore,
    ?int &$numPossibleWinners,
    int $offset = 0,
    int $limit = 50,
): Collection {

    $achievement = Achievement::firstWhere('id', $achievementId);
    if (!$achievement) {
        return new Collection();
    }

    $numWinners = $achievement->unlocks_total ?? 0;
    $numWinnersHardcore = $achievement->unlocks_hardcore ?? 0;
    $numPossibleWinners = $achievement->game->players_total ?? 0;

    // Get recent winners, and their most recent activity.
    return PlayerAchievement::where('achievement_id', $achievementId)
        ->join('users', 'users.id', '=', 'user_id')
        ->orderByDesc('unlocked_effective_at')
        ->select(['users.ulid', 'users.username', 'users.display_name', 'users.points_hardcore', 'users.points', 'unlocked_at', 'unlocked_hardcore_at'])
        ->offset($offset)
        ->limit($limit)
        ->get()
        ->map(function ($row) {
            return [
                'User' => !empty($row->display_name) ? $row->display_name : $row->username,
                'ULID' => $row->ulid,
                'RAPoints' => $row->points_hardcore,
                'RASoftcorePoints' => $row->points,
                'DateAwarded' => $row->unlocked_hardcore_at ?? $row->unlocked_at,
                'HardcoreMode' => $row->unlocked_hardcore_at ? 1 : 0,
            ];
        });
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

    $hasValidStart = (bool) strtotime($startTime);
    $hasValidEnd = (bool) strtotime($endTime);

    $userArray = [];
    foreach ($achievementIDs as $nextID) {
        $query = PlayerAchievement::query()
            ->join('users', 'users.id', '=', 'player_achievements.user_id')
            ->where('player_achievements.achievement_id', $nextID)
            ->whereNull('users.unranked_at');

        if ($hasValidStart && $hasValidEnd) {
            $query->whereBetween("player_achievements.$column", [$startTime, $endTime]);
        } elseif ($hasValidStart) {
            $query->where("player_achievements.$column", '>=', $startTime);
        } elseif ($hasValidEnd) {
            $query->where("player_achievements.$column", '<=', $endTime);
        } else {
            $query->whereNotNull("player_achievements.$column");
        }

        $usernames = $query
            ->orderBy('users.username')
            ->pluck('users.username')
            ->all();

        foreach ($usernames as $username) {
            $userArray[$nextID][] = $username;
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
    bool $isPromoted = true,
    int $numPlayers = 0,
): array {
    /** @var Game $game */
    $game = Game::withCount(['achievements' => fn ($query) => $query->where('is_promoted', $isPromoted)])->find($gameID);
    $results = null;

    if (!$game || !$game->achievements_count) {
        // NOTE this will return an empty array instead of an empty object. keep it like this for backwards compatibility.
        return [];
    }

    // if a game has more than 100 players, don't filter out the untracked users as the
    // join becomes very expensive. will be addressed when denormalized data is captured
    $shouldJoinUsers = $numPlayers < 100;

    // Returns an array of the number of players who have achieved each total, up to the max.
    if ($isPromoted) {
        $countColumn = $isHardcore ? 'player_games.achievements_unlocked_hardcore' : 'player_games.achievements_unlocked_softcore';

        $countQuery = DB::table("player_games")
            ->selectRaw("$countColumn as AwardedCount, count(*) as NumUniquePlayers")
            ->where("$countColumn", ">", 0)
            ->where('player_games.game_id', $gameID)
            ->groupBy('AwardedCount')
            ->orderByDesc('AwardedCount');

        if ($shouldJoinUsers) {
            $countQuery->join("users", "player_games.user_id", "=", "users.id")
                ->where(fn ($query) => $query
                    ->whereNull("users.unranked_at")
                    ->orWhere("users.username", $requestedBy)
            );
        }

        $results = $countQuery->get();
    } else {
        $countColumn = $isHardcore ? 'sub.hardcore_unlocks' : 'sub.casual_unlocks';

        $subQuery = PlayerAchievement::query()
            ->selectRaw(
                "player_achievements.user_id,
                sum(case when player_achievements.unlocked_hardcore_at is null then 1 else 0 end) as casual_unlocks,
                sum(case when player_achievements.unlocked_hardcore_at is not null then 1 else 0 end) as hardcore_unlocks"
            )
            ->join("achievements", "player_achievements.achievement_id", "=", "achievements.id")
            ->where(DB::raw("achievements.game_id"), $gameID)
            ->where(DB::raw("achievements.is_promoted"), 0)
            ->groupBy("player_achievements.user_id");

        if ($shouldJoinUsers) {
            $subQuery->join("users", "player_achievements.user_id", "=", "users.id")
                ->where(fn ($query) => $query
                    ->whereNull(DB::raw("users.unranked_at"))
                    ->orWhere(DB::raw("users.username"), $requestedBy)
            );
        }

        $countQuery = PlayerAchievement::query()
            ->fromSub($subQuery, "sub")
            ->selectRaw("$countColumn as AwardedCount, count(*) as NumUniquePlayers")
            ->where(DB::raw("$countColumn"), ">", 0)
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
