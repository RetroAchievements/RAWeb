<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Concerns;

use App\Platform\Enums\UnlockMode;
use App\Platform\Models\Achievement;
use App\Platform\Models\PlayerAchievementLegacy;
use App\Site\Models\User;
use Carbon\Carbon;

trait TestsPlayerAchievements
{
    protected function addPlayerAchievement(
        User $user,
        Achievement $achievement,
        ?Carbon $hardcoreUnlockTime,
        Carbon $softcoreUnlockTime
    ): void {
        // TODO use unlock action instead as soon as it's been refactored, drop the rest
        $user->achievements()->syncWithPivotValues(
            $achievement,
            [
                'unlocked_at' => $softcoreUnlockTime,
                'unlocked_hardcore_at' => $hardcoreUnlockTime,
            ],
            detaching: false
        );

        $needsHardcore = ($hardcoreUnlockTime !== null);
        $needsSoftcore = true;

        $unlocks = $user->playerAchievementsLegacy()->where('AchievementID', $achievement->ID)->get();
        foreach ($unlocks as $unlock) {
            if ($unlock['HardcoreMode'] === UnlockMode::Hardcore) {
                $needsHardcore = false;
            } else {
                $needsSoftcore = false;
            }
        }

        if ($needsHardcore) {
            $user->playerAchievementsLegacy()->save(
                new PlayerAchievementLegacy([
                    'User' => $user->User,
                    'AchievementID' => $achievement->ID,
                    'HardcoreMode' => UnlockMode::Hardcore,
                    'Date' => $hardcoreUnlockTime,
                ])
            );
        } elseif (!$needsSoftcore) {
            return;
        }

        if ($needsSoftcore) {
            $user->playerAchievementsLegacy()->save(
                new PlayerAchievementLegacy([
                    'User' => $user->User,
                    'AchievementID' => $achievement->ID,
                    'HardcoreMode' => UnlockMode::Softcore,
                    'Date' => $softcoreUnlockTime,
                ])
            );
        }
    }

    protected function addHardcoreUnlock(User $user, Achievement $achievement, ?Carbon $when = null): void
    {
        $this->addPlayerAchievement($user, $achievement, $when ?? Carbon::now(), $when ?? Carbon::now());
    }

    protected function addSoftcoreUnlock(User $user, Achievement $achievement, ?Carbon $when = null): void
    {
        $this->addPlayerAchievement($user, $achievement, null, $when ?? Carbon::now());
    }

    protected function assertHasUnlock(User $user, Achievement $achievement, int $mode): void
    {
        $query = $user->playerAchievements()->where('achievement_id', $achievement->ID);
        if ($mode === UnlockMode::Hardcore) {
            $query->whereNotNull('unlocked_hardcore_at');
        }
        $this->assertTrue(
            $query->exists(),
            "No " . UnlockMode::toString($mode) . " unlock for achievement " . $achievement->ID . "/user " . $user->ID
        );

        $this->assertTrue(
            $user->playerAchievementsLegacy()
                ->where('AchievementID', $achievement->ID)
                ->where('HardcoreMode', $mode)
                ->exists(),
            "No " . UnlockMode::toString($mode) . " unlock for achievement " . $achievement->ID . "/user " . $user->ID
        );
    }

    protected function assertHasHardcoreUnlock(User $user, Achievement $achievement): void
    {
        $this->assertHasUnlock($user, $achievement, UnlockMode::Hardcore);
    }

    protected function assertHasSoftcoreUnlock(User $user, Achievement $achievement): void
    {
        $this->assertHasUnlock($user, $achievement, UnlockMode::Softcore);
    }

    protected function assertDoesNotHaveUnlockInMode(User $user, Achievement $achievement, int $mode): void
    {
        $query = $user->playerAchievements()->where('achievement_id', $achievement->ID);
        if ($mode === UnlockMode::Hardcore) {
            $query->whereNotNull('unlocked_hardcore_at');
        }
        $this->assertFalse(
            $query->exists(),
            "Found " . UnlockMode::toString($mode) . " unlock for achievement " . $achievement->ID . "/user " . $user->ID
        );

        $this->assertFalse(
            $user->playerAchievementsLegacy()->where('AchievementID', $achievement->ID)
                ->where('HardcoreMode', $mode)->exists(),
            "Found legacy " . UnlockMode::toString($mode) . " unlock for achievement " . $achievement->ID . "/user " . $user->ID
        );
    }

    protected function assertDoesNotHaveHardcoreUnlock(User $user, Achievement $achievement): void
    {
        $this->assertDoesNotHaveUnlockInMode($user, $achievement, UnlockMode::Hardcore);
    }

    protected function assertDoesNotHaveSoftcoreUnlock(User $user, Achievement $achievement): void
    {
        $this->assertDoesNotHaveUnlockInMode($user, $achievement, UnlockMode::Softcore);
    }

    protected function assertDoesNotHaveAnyUnlock(User $user, Achievement $achievement): void
    {
        $this->assertFalse(
            $user->playerAchievements()->where('achievement_id', $achievement->ID)->exists(),
            "Found unlock for achievement " . $achievement->ID . "/user " . $user->ID
        );

        $this->assertFalse(
            $user->playerAchievementsLegacy()->where('AchievementID', $achievement->ID)->exists(),
            "Found legacy unlock for achievement " . $achievement->ID . "/user " . $user->ID
        );
    }

    protected function getUnlockTime(User $user, Achievement $achievement, int $mode): ?Carbon
    {
        $unlocks = $user->playerAchievementsLegacy()->where('AchievementID', $achievement->ID)->get();
        foreach ($unlocks as $unlock) {
            if ($unlock->HardcoreMode === $mode) {
                return $unlock->Date;
            }
        }

        return null;
    }
}
