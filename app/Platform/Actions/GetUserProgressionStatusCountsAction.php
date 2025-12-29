<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Models\PlayerBadge;
use App\Models\PlayerGame;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Enums\UnlockMode;
use Illuminate\Database\Eloquent\Builder;

class GetUserProgressionStatusCountsAction
{
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
        // Subsets are identified by their core achievement set being linked to another game as a non-core type.
        $subsetExistsSubquery = "EXISTS (
            SELECT 1 FROM game_achievement_sets gas1
            INNER JOIN game_achievement_sets gas2 ON gas2.achievement_set_id = gas1.achievement_set_id
            WHERE gas1.game_id = player_games.game_id
            AND gas1.type = '" . AchievementSetType::Core->value . "'
            AND gas2.game_id != gas1.game_id
            AND gas2.type != '" . AchievementSetType::Core->value . "'
        )";

        // Use SiteAwards (badges) for progression status counting.
        // This ensures "revised masteries" (where achievements were added after mastery) are still counted as mastered.
        $masteryAwardType = AwardType::Mastery;
        $beatenAwardType = AwardType::GameBeaten;
        $hardcoreMode = UnlockMode::Hardcore;
        $softcoreMode = UnlockMode::Softcore;

        $results = $this->buildQualifyingGamesQuery($user)
            ->selectRaw("
                games.system_id,
                SUM(player_games.achievements_unlocked_hardcore) as total_hc_achievements,
                SUM(GREATEST(0, CAST(player_games.achievements_unlocked AS SIGNED) - CAST(player_games.achievements_unlocked_hardcore AS SIGNED))) as total_sc_achievements,
                SUM(mastery_hc.id IS NOT NULL) as mastered_count,
                SUM(mastery_hc.id IS NULL AND mastery_sc.id IS NOT NULL) as completed_count,
                SUM(mastery_hc.id IS NULL AND mastery_sc.id IS NULL AND beaten_hc.id IS NOT NULL) as beaten_hc_count,
                SUM(mastery_hc.id IS NULL AND mastery_sc.id IS NULL AND beaten_hc.id IS NULL AND beaten_sc.id IS NOT NULL) as beaten_sc_count,
                SUM(
                    mastery_hc.id IS NULL
                    AND mastery_sc.id IS NULL
                    AND beaten_hc.id IS NULL
                    AND beaten_sc.id IS NULL
                    AND NOT {$subsetExistsSubquery}
                ) as unfinished_count
            ")
            ->leftJoin('SiteAwards as mastery_hc', function ($join) use ($masteryAwardType, $hardcoreMode) {
                $join->on('mastery_hc.user_id', '=', 'player_games.user_id')
                    ->on('mastery_hc.AwardData', '=', 'player_games.game_id')
                    ->where('mastery_hc.AwardType', '=', $masteryAwardType)
                    ->where('mastery_hc.AwardDataExtra', '=', $hardcoreMode);
            })
            ->leftJoin('SiteAwards as mastery_sc', function ($join) use ($masteryAwardType, $softcoreMode) {
                $join->on('mastery_sc.user_id', '=', 'player_games.user_id')
                    ->on('mastery_sc.AwardData', '=', 'player_games.game_id')
                    ->where('mastery_sc.AwardType', '=', $masteryAwardType)
                    ->where('mastery_sc.AwardDataExtra', '=', $softcoreMode);
            })
            ->leftJoin('SiteAwards as beaten_hc', function ($join) use ($beatenAwardType, $hardcoreMode) {
                $join->on('beaten_hc.user_id', '=', 'player_games.user_id')
                    ->on('beaten_hc.AwardData', '=', 'player_games.game_id')
                    ->where('beaten_hc.AwardType', '=', $beatenAwardType)
                    ->where('beaten_hc.AwardDataExtra', '=', $hardcoreMode);
            })
            ->leftJoin('SiteAwards as beaten_sc', function ($join) use ($beatenAwardType, $softcoreMode) {
                $join->on('beaten_sc.user_id', '=', 'player_games.user_id')
                    ->on('beaten_sc.AwardData', '=', 'player_games.game_id')
                    ->where('beaten_sc.AwardType', '=', $beatenAwardType)
                    ->where('beaten_sc.AwardDataExtra', '=', $softcoreMode);
            })
            ->groupBy('games.system_id')
            ->get();

        $systemProgress = [];
        $totalCounts = ['unfinished' => 0, 'beatenSoftcore' => 0, 'beatenHardcore' => 0, 'completed' => 0, 'mastered' => 0];
        $totalHardcoreAchievements = 0;
        $totalSoftcoreAchievements = 0;

        foreach ($results as $row) {
            $systemId = (int) $row->system_id;
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

        // Add counts for "orphan badges" - badges for games not in the main query.
        // This handles demoted games, games with < 6 achievements, etc.
        $this->addOrphanBadgeCounts($user, $systemProgress, $totalCounts);

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
     * Finds badges for games not included in the main query and adds them to the counts.
     * This handles edge cases like demoted games, etc.
     *
     * @param array<int, array{unfinishedCount: int, beatenSoftcoreCount: int, beatenHardcoreCount: int, completedCount: int, masteredCount: int, systemId: int}> $systemProgress
     * @param array{unfinished: int, beatenSoftcore: int, beatenHardcore: int, completed: int, mastered: int} $totalCounts
     */
    private function addOrphanBadgeCounts(User $user, array &$systemProgress, array &$totalCounts): void
    {
        $countedGameIds = $this->buildQualifyingGamesQuery($user)
            ->pluck('player_games.game_id')
            ->toArray();

        // Find awards for stuff _not_ in the main query.
        $orphanBadges = PlayerBadge::query()
            ->select([
                'SiteAwards.AwardData as game_id',
                'SiteAwards.AwardType',
                'SiteAwards.AwardDataExtra',
                'games.system_id',
            ])
            ->join('games', 'games.id', '=', 'SiteAwards.AwardData')
            ->join('Console', 'Console.ID', '=', 'games.system_id')
            ->where('SiteAwards.user_id', $user->id)
            ->whereIn('SiteAwards.AwardType', [AwardType::Mastery, AwardType::GameBeaten])
            ->whereNotIn('SiteAwards.AwardData', $countedGameIds)
            ->whereRaw('Console.active = true')
            ->whereNotIn('games.system_id', System::getNonGameSystems())
            ->get();

        // Group by game and find the highest award per game.
        $orphanGameAwards = [];
        $priority = ['mastered' => 4, 'completed' => 3, 'beatenHardcore' => 2, 'beatenSoftcore' => 1];

        foreach ($orphanBadges as $badge) {
            $gameId = $badge->game_id;
            $systemId = (int) $badge->system_id;

            $awardKind = match (true) {
                $badge->AwardType === AwardType::Mastery && $badge->AwardDataExtra === UnlockMode::Hardcore => 'mastered',
                $badge->AwardType === AwardType::Mastery && $badge->AwardDataExtra === UnlockMode::Softcore => 'completed',
                $badge->AwardType === AwardType::GameBeaten && $badge->AwardDataExtra === UnlockMode::Hardcore => 'beatenHardcore',
                $badge->AwardType === AwardType::GameBeaten && $badge->AwardDataExtra === UnlockMode::Softcore => 'beatenSoftcore',
                default => null,
            };

            if ($awardKind === null) {
                continue;
            }

            // Keep only the highest award per game.
            if (!isset($orphanGameAwards[$gameId]) || $priority[$awardKind] > $priority[$orphanGameAwards[$gameId]['kind']]) {
                $orphanGameAwards[$gameId] = ['kind' => $awardKind, 'systemId' => $systemId];
            }
        }

        // Add orphan badges to the counts.
        foreach ($orphanGameAwards as $award) {
            $systemId = $award['systemId'];
            $kind = $award['kind'];

            if (!isset($systemProgress[$systemId])) {
                $systemProgress[$systemId] = [
                    'unfinishedCount' => 0,
                    'beatenSoftcoreCount' => 0,
                    'beatenHardcoreCount' => 0,
                    'completedCount' => 0,
                    'masteredCount' => 0,
                    'systemId' => $systemId,
                ];
            }

            match ($kind) {
                'mastered' => [$systemProgress[$systemId]['masteredCount']++, $totalCounts['mastered']++],
                'completed' => [$systemProgress[$systemId]['completedCount']++, $totalCounts['completed']++],
                'beatenHardcore' => [$systemProgress[$systemId]['beatenHardcoreCount']++, $totalCounts['beatenHardcore']++],
                'beatenSoftcore' => [$systemProgress[$systemId]['beatenSoftcoreCount']++, $totalCounts['beatenSoftcore']++],
            };
        }
    }

    /**
     * @return Builder<PlayerGame>
     */
    private function buildQualifyingGamesQuery(User $user): Builder
    {
        return PlayerGame::where('player_games.user_id', $user->id)
            ->where('player_games.achievements_unlocked', '>', 0)
            ->join('games', 'games.id', '=', 'player_games.game_id')
            ->join('Console', 'Console.ID', '=', 'games.system_id')
            ->whereRaw('Console.active = true')
            ->whereRaw('games.achievements_published >= ?', [PlayerBadge::MINIMUM_ACHIEVEMENTS_COUNT_FOR_MASTERY])
            ->whereNotIn('games.system_id', System::getNonGameSystems());
    }

    /**
     * Calculates the user's average completion percentage, excluding subsets.
     * We identify subsets by if its core achievement set is also linked to another game as a non-core type.
     */
    private function calculateAvgCompletionExcludingSubsets(User $user): float
    {
        $result = PlayerGame::query()
            ->selectRaw('
                SUM(player_games.achievements_unlocked / games.achievements_published) as sum_pct,
                COUNT(*) as game_count
            ')
            ->join('games', 'games.id', '=', 'player_games.game_id')
            ->join('Console', 'Console.ID', '=', 'games.system_id')
            ->where('player_games.user_id', $user->id)
            ->where('player_games.achievements_unlocked', '>', 0)
            ->whereRaw('Console.active = true')
            ->whereRaw('games.achievements_published >= ?', [PlayerBadge::MINIMUM_ACHIEVEMENTS_COUNT_FOR_MASTERY])
            ->whereNotIn('games.system_id', System::getNonGameSystems())
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
