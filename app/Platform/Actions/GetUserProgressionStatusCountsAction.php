<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\PlayerGame;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\AchievementSetType;

class GetUserProgressionStatusCountsAction
{
    private const MIN_ACHIEVEMENTS = 5;

    /**
     * @return array{
     *     avgCompletionPercentage: float,
     *     systemProgress: array<int, array{unfinishedCount: int, beatenSoftcoreCount: int, beatenHardcoreCount: int, completedCount: int, masteredCount: int, systemId: int}>,
     *     topSystemId: int|null,
     *     totalCounts: array{unfinished: int, beatenSoftcore: int, beatenHardcore: int, completed: int, mastered: int},
     *     totalHardcoreAchievements: int,
     *     totalSoftcoreAchievements: int,
     * }
     */
    public function execute(User $user, ?int $recentSystemId = null): array
    {
        $subsetExistsSubquery = "EXISTS (
            SELECT 1 FROM game_achievement_sets gas1
            INNER JOIN game_achievement_sets gas2 ON gas2.achievement_set_id = gas1.achievement_set_id
            WHERE gas1.game_id = player_games.game_id
            AND gas1.type = '" . AchievementSetType::Core->value . "'
            AND gas2.game_id != gas1.game_id
            AND gas2.type != '" . AchievementSetType::Core->value . "'
        )";

        $results = PlayerGame::query()
            ->selectRaw("
                GameData.ConsoleID,
                SUM(player_games.achievements_unlocked_hardcore) as total_hc_achievements,
                SUM(player_games.achievements_unlocked - player_games.achievements_unlocked_hardcore) as total_sc_achievements,
                SUM(player_games.completed_hardcore_at IS NOT NULL) as mastered_count,
                SUM(player_games.completed_hardcore_at IS NULL AND player_games.completed_at IS NOT NULL) as completed_count,
                SUM(player_games.beaten_hardcore_at IS NOT NULL AND player_games.completed_at IS NULL) as beaten_hc_count,
                SUM(player_games.beaten_at IS NOT NULL AND player_games.beaten_hardcore_at IS NULL AND player_games.completed_at IS NULL) as beaten_sc_count,
                SUM(
                    player_games.completed_hardcore_at IS NULL
                    AND player_games.completed_at IS NULL
                    AND player_games.beaten_hardcore_at IS NULL
                    AND player_games.beaten_at IS NULL
                    AND NOT {$subsetExistsSubquery}
                ) as unfinished_count
            ")
            ->join('GameData', 'GameData.ID', '=', 'player_games.game_id')
            ->join('Console', 'Console.ID', '=', 'GameData.ConsoleID')
            ->where('player_games.user_id', $user->id)
            ->where('player_games.achievements_unlocked', '>', 0)
            ->whereRaw('Console.active = true')
            ->whereRaw('GameData.achievements_published > ?', [self::MIN_ACHIEVEMENTS])
            ->whereNotIn('GameData.ConsoleID', System::getNonGameSystems())
            ->groupBy('GameData.ConsoleID')
            ->get();

        $systemProgress = [];
        $totalCounts = ['unfinished' => 0, 'beatenSoftcore' => 0, 'beatenHardcore' => 0, 'completed' => 0, 'mastered' => 0];
        $totalHardcoreAchievements = 0;
        $totalSoftcoreAchievements = 0;

        foreach ($results as $row) {
            $systemId = (int) $row->ConsoleID;
            $mastered = (int) $row->mastered_count;
            $completed = (int) $row->completed_count;
            $beatenHc = (int) $row->beaten_hc_count;
            $beatenSc = (int) $row->beaten_sc_count;
            $unfinished = (int) $row->unfinished_count;

            $systemProgress[$systemId] = [
                'unfinishedCount' => $unfinished,
                'beatenSoftcoreCount' => $beatenSc,
                'beatenHardcoreCount' => $beatenHc,
                'completedCount' => $completed,
                'masteredCount' => $mastered,
                'systemId' => $systemId,
            ];

            $totalCounts['unfinished'] += $unfinished;
            $totalCounts['beatenSoftcore'] += $beatenSc;
            $totalCounts['beatenHardcore'] += $beatenHc;
            $totalCounts['completed'] += $completed;
            $totalCounts['mastered'] += $mastered;

            $totalHardcoreAchievements += (int) $row->total_hc_achievements;
            $totalSoftcoreAchievements += (int) $row->total_sc_achievements;
        }

        $avgCompletionPercentage = $this->calculateAvgCompletionExcludingSubsets($user);

        $topSystemId = ($recentSystemId !== null && isset($systemProgress[$recentSystemId]))
            ? $recentSystemId
            : array_key_first($systemProgress);

        // Sort: topSystem first, then by total games played descending.
        uasort($systemProgress, function ($a, $b) use ($topSystemId) {
            if ($a['systemId'] === $topSystemId) {
                return -1;
            }
            if ($b['systemId'] === $topSystemId) {
                return 1;
            }

            $sumA = $a['unfinishedCount'] + $a['beatenSoftcoreCount'] + $a['beatenHardcoreCount'] + $a['completedCount'] + $a['masteredCount'];
            $sumB = $b['unfinishedCount'] + $b['beatenSoftcoreCount'] + $b['beatenHardcoreCount'] + $b['completedCount'] + $b['masteredCount'];

            return $sumB <=> $sumA;
        });

        return [
            'avgCompletionPercentage' => $avgCompletionPercentage,
            'systemProgress' => $systemProgress,
            'topSystemId' => $topSystemId,
            'totalCounts' => $totalCounts,
            'totalHardcoreAchievements' => $totalHardcoreAchievements,
            'totalSoftcoreAchievements' => $totalSoftcoreAchievements,
        ];
    }

    /**
     * Calculates the user's average completion percentage, excluding subsets.
     * We identify subsets by if its core achievement set is also linked to another game as a non-core type.
     */
    private function calculateAvgCompletionExcludingSubsets(User $user): float
    {
        $result = PlayerGame::query()
            ->selectRaw('
                SUM(player_games.achievements_unlocked / GameData.achievements_published) as sum_pct,
                COUNT(*) as game_count
            ')
            ->join('GameData', 'GameData.ID', '=', 'player_games.game_id')
            ->join('Console', 'Console.ID', '=', 'GameData.ConsoleID')
            ->where('player_games.user_id', $user->id)
            ->where('player_games.achievements_unlocked', '>', 0)
            ->whereRaw('Console.active = true')
            ->whereRaw('GameData.achievements_published > ?', [self::MIN_ACHIEVEMENTS])
            ->whereNotIn('GameData.ConsoleID', System::getNonGameSystems())
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('game_achievement_sets as gas1')
                    ->join('game_achievement_sets as gas2', 'gas2.achievement_set_id', '=', 'gas1.achievement_set_id')
                    ->whereColumn('gas1.game_id', 'player_games.game_id')
                    ->where('gas1.type', AchievementSetType::Core)
                    ->whereColumn('gas2.game_id', '!=', 'gas1.game_id')
                    ->where('gas2.type', '!=', AchievementSetType::Core);
            })
            ->first();

        // prevent division by zero
        if (!$result || $result->game_count == 0) {
            return 0.0;
        }

        return round(((float) $result->sum_pct / (int) $result->game_count) * 100, 2);
    }
}
