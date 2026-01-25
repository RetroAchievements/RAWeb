<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\PlayerStat;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\Concerns\CalculatesPlayerPointsStats;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UpdatePlayerPointsStatsAction
{
    use CalculatesPlayerPointsStats;

    public function execute(User $user): void
    {
        // If the user is untracked, wipe any stats they
        // already have and then immediately bail. If/when
        // they're retracked, we can regenerate their stats.
        if ($user->is_unranked) {
            $this->clearExistingUntrackedStats($user);

            return;
        }

        // Fetch all the player achievements that we care about
        // for stats tracking. We'll slightly overfetch with a
        // 8 day window and throw some stuff away in a bit.
        $recentPlayerAchievements = $user->playerAchievements()
            ->whereBetween('unlocked_at', [Carbon::now()->subDays(8), Carbon::now()])
            ->with(['achievement.game' => function ($query) {
                $query->where('system_id', '!=', System::Events);
            }])
            ->whereHas('achievement.game', function ($query) {
                $query->where('system_id', '!=', System::Events);
            })
            ->get();

        // Separate achievements by type.
        ['hardcore' => $hardcoreAchievements, 'softcore' => $softcoreAchievements] =
            $this->separateAchievementsByType($recentPlayerAchievements);

        $statIntervals = $this->getStatIntervals();

        foreach ($statIntervals as $period => $interval) {
            $hardcorePoints = $this->calculatePointsForInterval($hardcoreAchievements, $interval);
            $softcorePoints = $this->calculatePointsForInterval($softcoreAchievements, $interval);

            $this->upsertAllPlayerPointsStats($user, $hardcorePoints, $softcorePoints, $period);
        }
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
        $statTypes = static::PERIOD_MAP[$period];

        // Hardcore points.
        $this->writePlayerPointsStat(
            $user,
            $statTypes['hardcore'],
            $hardcorePoints['points'],
        );

        // Weighted points.
        $this->writePlayerPointsStat(
            $user,
            $statTypes['weighted'],
            $hardcorePoints['points_weighted'],
        );

        // Softcore points.
        $this->writePlayerPointsStat(
            $user,
            $statTypes['softcore'],
            $softcorePoints['points'],
        );
    }

    /**
     * Performs a create, update, or delete based on the current state:
     * - No record exists and points > 0: create new stat.
     * - Record exists and points > 0: update if value changed.
     * - Record exists and points = 0: delete the stat.
     *
     * Uses a transaction with 3 retries to handle potential deadlocks.
     */
    private function writePlayerPointsStat(
        User $user,
        string $playerStatType,
        int $points,
    ): void {
        $maxRetries = 3;

        DB::transaction(function () use ($user, $playerStatType, $points) {
            $attributes = ['user_id' => $user->id, 'type' => $playerStatType];
            $existingPlayerStat = PlayerStat::where($attributes)->first();

            if (!$existingPlayerStat) {
                if ($points !== 0) {
                    PlayerStat::create([...$attributes, 'value' => $points]);
                }

                return;
            }

            if ($points === 0) {
                $existingPlayerStat->delete();

                return;
            }

            if ($existingPlayerStat->value !== $points) {
                $existingPlayerStat->update(['value' => $points]);
            }
        }, $maxRetries);
    }
}
