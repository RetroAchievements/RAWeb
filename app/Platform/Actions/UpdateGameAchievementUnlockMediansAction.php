<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Models\PlayerGame;
use App\Models\PlayerProgressReset;
use App\Models\PlayerSession;
use App\Platform\Enums\PlayerProgressResetType;
use Illuminate\Support\Collection;

class UpdateGameAchievementUnlockMediansAction
{
    public function execute(Game $game): void
    {
        $achievements = $game->achievements()->promoted()->get();
        if ($achievements->isEmpty()) {
            return;
        }

        $recentCasualPlayerIds = $this->getRecentPlayerIds($game, false);
        $recentHardcorePlayerIds = $this->getRecentPlayerIds($game, true);
        $recentPlayerIds = array_unique(array_merge($recentCasualPlayerIds, $recentHardcorePlayerIds));

        [$unlock_times, $unlock_hardcore_times] = $this->calculateUnlockTimes($game, $achievements, $recentPlayerIds);

        foreach ($achievements as $achievement) {
            $achievement->median_time_to_unlock_samples = count($unlock_times[$achievement->id]);
            $achievement->median_time_to_unlock = $this->getMedian($unlock_times[$achievement->id]);
            $achievement->median_time_to_unlock_hardcore_samples = count($unlock_hardcore_times[$achievement->id]);
            $achievement->median_time_to_unlock_hardcore = $this->getMedian($unlock_hardcore_times[$achievement->id]);
            $achievement->saveQuietly();
        }
    }

    private function getRecentPlayerIds(Game $game, bool $hardcore): array
    {
        // If the game takes more than 5 hours to master, there will likely be multiple sessions per user, so
        // scale the number of players we want to examine to prevent the player_sessions query from exploding.
        $coreSet = $game->gameAchievementSets()->core()->first()?->achievementSet;
        $timeToMaster = $hardcore ? $coreSet?->median_time_to_complete_hardcore : $coreSet?->median_time_to_complete;
        // If the time to master is unknown, assume double the time to beat
        $timeToMaster ??= (($hardcore ? $game->median_time_to_beat_hardcore : $game->median_time_to_beat) ?? 0) * 2;
        // Convert to hours
        $hoursToMaster = $timeToMaster / 3600;

        $targetCount = match (true) {
            $hoursToMaster > 20 => 60,
            $hoursToMaster > 10 => 100,
            $hoursToMaster > 5 => 150,
            default => 200,
        };

        // If the set has more than 500 players, only look at players who have earned at least half the achievements.
        // If the set has between 200 and 500 players, only look at players who have earned at least 25% of the achievements.
        // Otherwise, only look at players who have earned at least two achievements.
        $unlockThresholds = [
            (int) floor($game->achievements_published / 2),
            (int) floor($game->achievements_published / 4),
            2,
        ];
        $players = $hardcore ? $game->players_hardcore : $game->players_total - $game->players_hardcore;
        $unlockThresholdIndex = ($players > 500) ? 0 : (($players > 200) ? 1 : 2);

        $recentPlayerIds = [];
        do {
            $unlockThreshold = $unlockThresholds[$unlockThresholdIndex++];

            // For hardcore, get players with the target number of hardcore unlocks.
            // For casual, get players with the target number of unlocks where at least half were casual.
            $playerIds = PlayerGame::query()
                ->where('game_id', $game->id)
                ->when($hardcore, fn ($q) => $q->where('achievements_unlocked_hardcore', '>=', $unlockThreshold))
                ->when(!$hardcore, fn ($q) => $q->where('achievements_unlocked', '>=', $unlockThreshold)->whereRaw('achievements_unlocked_hardcore <= achievements_unlocked / 2'))
                ->leftJoin('unranked_users', 'player_games.user_id', '=', 'unranked_users.user_id')
                ->whereNull('unranked_users.id')
                ->orderByDesc('last_unlock_at')
                ->limit($targetCount)
                ->toBase()
                ->pluck('player_games.user_id')
                ->toArray();

            // Previous thresholds contain more interesting data. Merge in new results so any
            // old results matching the higher threshold aren't lost.
            $recentPlayerIds = array_unique(array_merge($recentPlayerIds, $playerIds));

            // If the threshold eliminated too many results, try again at the next threshold.
        } while (count($recentPlayerIds) < $targetCount && $unlockThreshold > 2);

        // If we haven't captured at least five users who have mastered the set, find the
        // five most recent masters and include them.
        $numMasters = PlayerGame::query()
            ->where('game_id', $game->id)
            ->whereIn('user_id', $recentPlayerIds)
            ->where($hardcore ? 'achievements_unlocked_hardcore' : 'achievements_unlocked', $game->achievements_published)
            ->count();
        if ($numMasters < 5) {
            $playerIds = PlayerGame::query()
                ->where('game_id', $game->id)
                ->where($hardcore ? 'achievements_unlocked_hardcore' : 'achievements_unlocked', $game->achievements_published)
                ->leftJoin('unranked_users', 'player_games.user_id', '=', 'unranked_users.user_id')
                ->whereNull('unranked_users.id')
                ->orderByDesc('last_unlock_at')
                ->limit(5)
                ->toBase()
                ->pluck('player_games.user_id')
                ->toArray();

            $recentPlayerIds = array_unique(array_merge($recentPlayerIds, $playerIds));
        }

        return $recentPlayerIds;
    }

    /**
     * @param Collection<int, Achievement> $achievements
     */
    private function calculateUnlockTimes(Game $game, Collection $achievements, array $recentPlayerIds): array
    {
        $coreSet = $game->gameAchievementSets()->core()->first()?->achievementSet;
        $achievementsFirstPublishedAt = $coreSet?->achievements_first_published_at;
        $achievementsFirstPublishedAtTimestamp = $achievementsFirstPublishedAt?->getTimestamp();
        $achievementIds = $achievements->pluck('id');

        // ===== Get unlocks for each recent player =====
        $achievementUnlocks = PlayerAchievement::query()
            ->forceIndex('player_achievements_achievement_id_user_id_unlocked_hardcore_at')
            ->whereIn('user_id', $recentPlayerIds)
            ->whereIn('achievement_id', $achievementIds)
            ->whereNull('unlocker_id')
            ->select(['user_id', 'achievement_id'])
            ->selectRaw(unixTimestampStatement('unlocked_at', 'unlocked_at_timestamp'))
            ->selectRaw(unixTimestampStatement('unlocked_hardcore_at', 'unlocked_hardcore_at_timestamp'))
            ->toBase()
            ->get();

        $unlocks = [];
        foreach ($achievementUnlocks as $unlock) {
            $unlockedAt = $unlock->unlocked_at_timestamp ? (int) $unlock->unlocked_at_timestamp : null;
            $unlockedHardcoreAt = $unlock->unlocked_hardcore_at_timestamp ? (int) $unlock->unlocked_hardcore_at_timestamp : null;
            $unlockedEffectiveAt = $unlockedHardcoreAt ?? $unlockedAt;

            if (!array_key_exists($unlock->user_id, $unlocks)) {
                $unlocks[$unlock->user_id] = [];

                // if the first published at timestamp hasn't been calculated yet, use the first unlock we found
                if (!$achievementsFirstPublishedAtTimestamp && $unlockedAt) {
                    $achievementsFirstPublishedAtTimestamp = $unlockedAt - 600; // give 10 minutes of lead time to first unlock
                    $achievementsFirstPublishedAt = date('Y-m-d H:i:s', $achievementsFirstPublishedAtTimestamp);
                }
            }

            $unlocks[$unlock->user_id][] = [
                'achievement_id' => $unlock->achievement_id,
                'unlocked_at' => $unlockedAt,
                'unlocked_hardcore_at' => $unlockedHardcoreAt,
                'unlocked_effective_at' => $unlockedEffectiveAt,
            ];
        }

        // ===== Sort the unlocks for each user =====
        foreach ($unlocks as &$userUnlocks) {
            usort(
                $userUnlocks,
                fn (array $a, array $b): int => ($b['unlocked_effective_at'] ?? 0) <=> ($a['unlocked_effective_at'] ?? 0),
            );
        }
        unset($userUnlocks); // prevent corruption when new $userUnlocks is assigned below

        // ===== Process all sessions for recent players, calculating distance to each unlock =====
        $resets = PlayerProgressReset::query()
            ->where('type', PlayerProgressResetType::Game)
            ->where('type_id', $game->id)
            ->whereIn('user_id', $recentPlayerIds)
            ->pluck('created_at', 'user_id')
            ->toBase()
            ->map(fn ($createdAt): int => $createdAt->getTimestamp());

        $unlock_times = [];
        $unlock_hardcore_times = [];
        foreach ($achievementIds as $achievementId) {
            $unlock_times[$achievementId] = [];
            $unlock_hardcore_times[$achievementId] = [];
        }

        foreach ($unlocks as $userId => $userUnlocks) {
            // unlocks are sorted by date desc, so the first element is this user's latest unlock
            $latestUserUnlockAt = $userUnlocks[array_key_first($userUnlocks)]['unlocked_effective_at'] ?? null;
            $elapsed = 0;

            $sessions = PlayerSession::query()
                ->where('game_id', $game->id)
                ->where('user_id', $userId)
                ->when($achievementsFirstPublishedAt, fn ($q) => $q->where('rich_presence_updated_at', '>', $achievementsFirstPublishedAt))
                ->when($latestUserUnlockAt, fn ($q) => $q->where('created_at', '<=', date('Y-m-d H:i:s', $latestUserUnlockAt)))
                ->select(['user_id', 'duration'])
                ->selectRaw(unixTimestampStatement('created_at', 'created_at_timestamp'))
                ->selectRaw(unixTimestampStatement('rich_presence_updated_at', 'rich_presence_updated_at_timestamp'))
                ->orderBy('created_at')
                ->toBase()
                ->cursor();

            foreach ($sessions as $session) {
                if (empty($userUnlocks)) {
                    break;
                }

                $sessionCreatedAt = (int) $session->created_at_timestamp;
                $sessionStartBoundary = $resets[$userId] ?? $achievementsFirstPublishedAtTimestamp;
                $sessionStart = $sessionStartBoundary ? max($sessionStartBoundary, $sessionCreatedAt) : $sessionCreatedAt;
                $sessionEnd = max((int) $session->rich_presence_updated_at_timestamp, $sessionCreatedAt + ((int) $session->duration * 60));

                if ($sessionStartBoundary && $sessionEnd < $sessionStartBoundary) {
                    // Ignore sessions prior to the achievements being published or the player's last full reset
                    continue;
                }

                do {
                    // Unlocks are sorted by date desc, so the last element will be the earliest
                    $unlock = end($userUnlocks);

                    if ($unlock['unlocked_hardcore_at']) {
                        if ($unlock['unlocked_hardcore_at'] < $sessionStart || $unlock['unlocked_hardcore_at'] > $sessionEnd) {
                            break;
                        }

                        $unlock_hardcore_times[$unlock['achievement_id']][] = $elapsed +
                            ($unlock['unlocked_hardcore_at'] - $sessionStart);

                        if ($unlock['unlocked_at'] && $unlock['unlocked_at'] != $unlock['unlocked_hardcore_at']
                            && $unlock['unlocked_at'] >= $sessionStart && $unlock['unlocked_at'] <= $sessionEnd) {
                            $unlock_times[$unlock['achievement_id']][] = $elapsed +
                                ($unlock['unlocked_at'] - $sessionStart);
                        }
                    } elseif ($unlock['unlocked_at']) {
                        if ($unlock['unlocked_at'] < $sessionStart || $unlock['unlocked_at'] > $sessionEnd) {
                            break;
                        }

                        $unlock_times[$unlock['achievement_id']][] = $elapsed +
                            ($unlock['unlocked_at'] - $sessionStart);
                    }

                    // Remove processed element so we don't try to process it again.
                    array_pop($userUnlocks);
                } while (!empty($userUnlocks));

                $elapsed += $sessionEnd - $sessionStart;
            }
        }

        return [$unlock_times, $unlock_hardcore_times];
    }

    private function getMedian(array $a): int
    {
        $length = count($a);
        if ($length === 0) {
            return 0;
        }

        $values = array_values($a);
        sort($values);

        $index = (int) floor($length / 2);
        if (($length % 2) == 1) {
            return $values[$index];
        }

        return (int) round(($values[$index - 1] + $values[$index]) / 2);
    }
}
