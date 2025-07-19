<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\PlayerAchievement;
use App\Models\PlayerStat;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\Concerns\CalculatesPlayerPointsStats;
use Illuminate\Support\Facades\DB;

class UpdatePlayerPointsStatsBatchAction
{
    use CalculatesPlayerPointsStats;

    /**
     * Process a batch of users and update their points stats using bulk operations.
     *
     * @param array<int> $userIds
     */
    public function execute(array $userIds): void
    {
        if (empty($userIds)) {
            return;
        }

        $now = now();

        // Get users and handle untracked users.
        $users = User::whereIn('id', $userIds)->get();

        // Wipe stats for untracked users.
        $untrackedUserIds = $users->whereNotNull('unranked_at')->pluck('id')->toArray();
        if (!empty($untrackedUserIds)) {
            PlayerStat::whereIn('user_id', $untrackedUserIds)->delete();
        }

        // Process tracked users.
        $trackedUsers = $users->whereNull('unranked_at');
        if ($trackedUsers->isEmpty()) {
            return;
        }

        $trackedUserIds = $trackedUsers->pluck('ID')->toArray();

        // Fetch all player achievements for tracked users in the time window.
        $allAchievements = PlayerAchievement::whereIn('player_achievements.user_id', $trackedUserIds)
            ->whereBetween('player_achievements.unlocked_at', [$now->copy()->subDays(8), $now])
            ->join('Achievements', 'player_achievements.achievement_id', '=', 'Achievements.ID')
            ->join('GameData', 'Achievements.GameID', '=', 'GameData.ID')
            ->whereNotIn('GameData.ConsoleID', System::getNonGameSystems())
            ->select(
                'player_achievements.user_id',
                'player_achievements.unlocked_at',
                'player_achievements.unlocked_hardcore_at',
                'Achievements.Points as points',
                'Achievements.TrueRatio as points_weighted'
            )
            ->get()
            ->groupBy('user_id');

        // Calculate stats for each user.
        $statIntervals = $this->getStatIntervals();
        $bulkStats = [];

        foreach ($trackedUserIds as $userId) {
            $userAchievements = $allAchievements->get($userId, collect());

            // Separate achievements by type.
            ['hardcore' => $hardcoreAchievements, 'softcore' => $softcoreAchievements] =
                $this->separateAchievementsByType($userAchievements);

            foreach ($statIntervals as $period => $interval) {
                $hardcorePoints = $this->calculatePointsForInterval($hardcoreAchievements, $interval);
                $softcorePoints = $this->calculatePointsForInterval($softcoreAchievements, $interval);

                $periodStats = $this->buildStatsForPeriod($userId, $hardcorePoints, $softcorePoints, $period);

                // Add timestamps for bulk insert.
                foreach ($periodStats as $stat) {
                    $bulkStats[] = array_merge($stat, [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        // Get existing stats to check for needed changes and/or deletions.
        $statTypes = array_merge(
            array_values(static::PERIOD_MAP['day']),
            array_values(static::PERIOD_MAP['week'])
        );

        $existingStats = PlayerStat::whereIn('user_id', $trackedUserIds)
            ->whereIn('type', $statTypes)
            ->get();

        $existingStatsMap = $existingStats->mapWithKeys(fn ($stat) => ["{$stat->user_id}:{$stat->type}" => $stat]);

        // Build a map of stats that should exist (non-zero values).
        $expectedStatsMap = collect($bulkStats)->mapWithKeys(fn ($stat) => ["{$stat['user_id']}:{$stat['type']}" => $stat]);

        // Find stats to delete (already existing records, but should now be 0).
        $statsToDelete = [];
        foreach ($trackedUserIds as $userId) {
            foreach ($statTypes as $statType) {
                $key = "{$userId}:{$statType}";
                if ($existingStatsMap->has($key) && !$expectedStatsMap->has($key)) {
                    $statsToDelete[] = ['user_id' => $userId, 'type' => $statType];
                }
            }
        }

        // Delete stats that should now be 0.
        if (!empty($statsToDelete)) {
            foreach ($statsToDelete as $stat) {
                PlayerStat::where('user_id', $stat['user_id'])
                    ->where('type', $stat['type'])
                    ->delete();
            }
        }

        // Filter out unchanged stats for upsert.
        $statsToUpsert = collect($bulkStats)->filter(function ($stat) use ($existingStatsMap) {
            $key = "{$stat['user_id']}:{$stat['type']}";
            $existing = $existingStatsMap->get($key);

            return !$existing || $existing->value !== $stat['value'];
        })->values()->toArray();

        // Perform the bulk upsert.
        if (!empty($statsToUpsert)) {
            // SQLite explodes when it encounters these upserts.
            if (DB::connection()->getDriverName() === 'sqlite') {
                foreach ($statsToUpsert as $stat) {
                    PlayerStat::updateOrCreate(
                        ['user_id' => $stat['user_id'], 'type' => $stat['type']],
                        ['value' => $stat['value']]
                    );
                }

                return;
            }

            PlayerStat::upsert(
                $statsToUpsert,
                ['user_id', 'type'], // unique keys
                ['value', 'updated_at'] // columns to update
            );
        }
    }
}
