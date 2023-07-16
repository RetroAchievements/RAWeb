<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

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
        Carbon $softcoreUnlockTime): void
    {
        $needsHardcore = ($hardcoreUnlockTime !== null);
        $needsSoftcore = true;

        $unlocks = $user->playerAchievementsLegacy()->where('AchievementID', $achievement->id)->get();
        foreach ($unlocks as $unlock) {
            if ($unlock['HardcoreMode'] === UnlockMode::Hardcore) {
                $needsHardcore = false;
            } else {
                $needsSoftcore = false;
            }
        }

        if ($needsHardcore) {
            $unlock = new PlayerAchievementLegacy([
                'User' => $user->User,
                'AchievementID' => $achievement->ID,
                'HardcoreMode' => UnlockMode::Hardcore,
                'Date' => $hardcoreUnlockTime,
            ]);
            $user->playerAchievementsLegacy()->save($unlock);
        } elseif (!$needsSoftcore) {
            return;
        }

        if ($needsSoftcore) {
            $unlock = new PlayerAchievementLegacy([
                'User' => $user->User,
                'AchievementID' => $achievement->ID,
                'HardcoreMode' => UnlockMode::Softcore,
                'Date' => $softcoreUnlockTime,
            ]);
            $user->playerAchievementsLegacy()->save($unlock);
        }
    }

    protected function addHardcoreUnlock(User $user, Achievement $achievement, ?Carbon $when = null): void
    {
        $when ??= Carbon::now();
        $this->addPlayerAchievement($user, $achievement, $when, $when);
    }

    protected function addSoftcoreUnlock(User $user, Achievement $achievement, ?Carbon $when = null): void
    {
        $when ??= Carbon::now();
        $this->addPlayerAchievement($user, $achievement, null, $when);
    }

    protected function assertHasUnlock(User $user, Achievement $achievement, int $mode): void
    {
        $unlocks = $user->playerAchievementsLegacy()->where('AchievementID', $achievement->ID)->get();
        foreach ($unlocks as $unlock) {
            if ($unlock->HardcoreMode === $mode) {
                $this->assertTrue(true);

                return;
            }
        }

        $this->fail("No " . UnlockMode::toString($mode) . " unlock for achievement " .
                    $achievement->ID . "/user " . $user->ID);
    }

    protected function assertHasHardcoreUnlock(User $user, Achievement $achievement): void
    {
        $this->assertHasUnlock($user, $achievement, UnlockMode::Hardcore);
    }

    protected function assertHasSoftcoreUnlock(User $user, Achievement $achievement): void
    {
        $this->assertHasUnlock($user, $achievement, UnlockMode::Softcore);
    }

    protected function assertDoesNotHaveUnlock(User $user, Achievement $achievement, int $mode): void
    {
        $unlocks = $user->playerAchievementsLegacy()->where('AchievementID', $achievement->ID)->get();
        foreach ($unlocks as $unlock) {
            if ($unlock->HardcoreMode === $mode) {
                $this->fail("Found " . UnlockMode::toString($mode) . " unlock for achievement " .
                            $achievement->ID . "/user " . $user->ID);
            }
        }

        $this->assertTrue(true);
    }

    protected function assertDoesNotHaveHardcoreUnlock(User $user, Achievement $achievement): void
    {
        $this->assertDoesNotHaveUnlock($user, $achievement, UnlockMode::Hardcore);
    }

    protected function assertDoesNotHaveSoftcoreUnlock(User $user, Achievement $achievement): void
    {
        $this->assertDoesNotHaveUnlock($user, $achievement, UnlockMode::Softcore);
    }

    protected function assertDoesNotHaveAnyUnlock(User $user, Achievement $achievement): void
    {
        $unlocks = $user->playerAchievementsLegacy()->where('AchievementID', $achievement->ID)->get();
        foreach ($unlocks as $unlock) {
            $this->fail("Found " . UnlockMode::toString($unlock->HardcoreMode) . " unlock for achievement " .
                        $achievement->ID . "/user " . $user->ID);
        }

        $this->assertTrue(true);
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
