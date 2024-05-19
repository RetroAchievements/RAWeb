<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\PlayerStat;
use App\Models\User;
use App\Platform\Enums\PlayerStatType;
use App\Platform\Events\PlayerPointsStatsUpdated;
use Illuminate\Support\Carbon;

class UpdatePlayerPointsStats
{
    private const PERIOD_MAP = [
        'day' => [
            'hardcore' => PlayerStatType::PointsHardcoreDay,
            'softcore' => PlayerStatType::PointsSoftcoreDay,
            'weighted' => PlayerStatType::PointsWeightedDay,
        ],
        'week' => [
            'hardcore' => PlayerStatType::PointsHardcoreWeek,
            'softcore' => PlayerStatType::PointsSoftcoreWeek,
            'weighted' => PlayerStatType::PointsWeightedWeek,
        ],
    ];

    public function execute(User $user): void
    {
        // If the user is untracked, wipe any stats they
        // already have and then immediately bail. If/when
        // they're retracked, we can regenerate their stats.
        if ($user->Untracked) {
            $this->clearExistingUntrackedStats($user);

            return;
        }

        // Fetch all the player achievements that we care about
        // for stats tracking. We'll slightly overfetch with a
        // 8 day window and throw some stuff away in a bit.
        $recentPlayerAchievements = $user->playerAchievements()
            ->whereBetween('unlocked_at', [Carbon::now()->subDays(8), Carbon::now()])
            ->with('achievement')
            ->get();

        // Next, separate the hardcore earned achievements from the
        // softcore earned achievements.
        $hardcoreAchievements = $recentPlayerAchievements->filter(function ($playerAchievement) {
            return $playerAchievement->unlocked_hardcore_at !== null;
        });
        $softcoreAchievements = $recentPlayerAchievements->filter(function ($playerAchievement) {
            return $playerAchievement->unlocked_hardcore_at === null;
        });

        // "day" will be over the last 24 hours.
        // "week" will be the beginning of the week (server time).
        $statIntervals = [
            'day' => Carbon::now()->subDay(),
            'week' => Carbon::now()->startOfWeek(),
        ];

        foreach ($statIntervals as $key => $statInterval) {
            $hardcorePoints = $this->calculatePointsForPlayerAchievementsOfInterval($hardcoreAchievements, $statInterval);
            $softcorePoints = $this->calculatePointsForPlayerAchievementsOfInterval($softcoreAchievements, $statInterval);

            $this->upsertAllPlayerPointsStats($user, $hardcorePoints, $softcorePoints, $key);
        }
    }

    private function calculatePointsForPlayerAchievementsOfInterval(
        mixed $playerAchievements,
        Carbon $interval,
    ): array {
        $playerAchievementsOfInterval = $playerAchievements->filter(function ($playerAchievement) use ($interval) {
            return $playerAchievement->unlocked_at >= $interval;
        });

        $sumPoints = $playerAchievementsOfInterval->sum(function ($playerAchievement) {
            return $playerAchievement->achievement->points;
        });

        $sumPointsWeighted = $playerAchievementsOfInterval->sum(function ($playerAchievement) {
            return $playerAchievement->achievement->points_weighted;
        });

        return [
            'points' => $sumPoints ?? 0,
            'points_weighted' => $sumPointsWeighted ?? 0,
        ];
    }

    private function clearExistingUntrackedStats(User $user): void
    {
        PlayerStat::where('user_id', $user->id)->delete();
    }

    private function upsertAllPlayerPointsStats(
        User $user,
        array $hardcorePoints,
        array $softcorePoints,
        string $period,
    ): void {
        $statTypes = self::PERIOD_MAP[$period];

        // Hardcore points
        $this->writePlayerPointsStat(
            $user,
            $statTypes['hardcore'],
            $hardcorePoints['points'],
        );

        // Weighted Points
        $this->writePlayerPointsStat(
            $user,
            $statTypes['weighted'],
            $hardcorePoints['points_weighted'],
        );

        // Softcore Points
        $this->writePlayerPointsStat(
            $user,
            $statTypes['softcore'],
            $softcorePoints['points'],
        );
    }

    /**
     * This function will either perform a create, edit, or delete:
     * - If no record exists, we'll create.
     * - If a record already exists, has a non-zero value, and is receiving a new non-zero value, we'll update.
     */
    private function writePlayerPointsStat(
        User $user,
        string $playerStatType,
        int $points,
    ): void {
        $attributes = ['user_id' => $user->id, 'type' => $playerStatType];
        $existingPlayerStat = PlayerStat::where($attributes)->first();

        if ($existingPlayerStat) {
            $existingPlayerStat->update(['value' => $points]);
        } elseif ($points !== 0) {
            PlayerStat::create(array_merge($attributes, ['value' => $points]));
        }

        PlayerPointsStatsUpdated::dispatch($user);
    }
}
