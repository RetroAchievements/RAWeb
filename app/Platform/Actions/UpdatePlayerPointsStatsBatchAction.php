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
            ->whereBetween('player_achievements.unlocked_at', [$now->subDays(8), $now])
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

        // Get existing stats to check for changes.
        $existingStats = PlayerStat::whereIn('user_id', $trackedUserIds)
            ->whereIn('type', array_merge(
                array_values(static::PERIOD_MAP['day']),
                array_values(static::PERIOD_MAP['week'])
            ))
            ->get()
            ->mapWithKeys(fn ($stat) => ["{$stat->user_id}:{$stat->type}" => $stat->value]);

        // Filter out unchanged stats.
        $statsToUpsert = collect($bulkStats)->filter(function ($stat) use ($existingStats) {
            $key = "{$stat['user_id']}:{$stat['type']}";

            return !$existingStats->has($key) || $existingStats->get($key) !== $stat['value'];
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
