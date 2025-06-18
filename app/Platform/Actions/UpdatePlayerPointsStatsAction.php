<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\PlayerStat;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\Concerns\CalculatesPlayerPointsStats;
use Illuminate\Support\Carbon;

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
                $query->where('ConsoleID', '!=', System::Events);
            }])
            ->whereHas('achievement.game', function ($query) {
                $query->where('ConsoleID', '!=', System::Events);
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
     * This function will either perform a create, edit, or delete:
     * - If no record exists and points > 0, we'll create.
     * - If a record exists and points > 0, we'll update.
     * - If a record exists and points = 0, we'll delete.
     */
    private function writePlayerPointsStat(
        User $user,
        string $playerStatType,
        int $points,
    ): void {
        $attributes = ['user_id' => $user->id, 'type' => $playerStatType];
        $existingPlayerStat = PlayerStat::where($attributes)->first();

        if ($existingPlayerStat) {
            if ($points === 0) {
                $existingPlayerStat->delete();
            } else {
                $existingPlayerStat->value = $points;
                $existingPlayerStat->save();
            }
        } elseif ($points !== 0) {
            PlayerStat::create(array_merge($attributes, ['value' => $points]));
        }
    }
}
