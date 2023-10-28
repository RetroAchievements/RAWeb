<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Concerns;

use App\Platform\Actions\UnlockPlayerAchievement;
use App\Platform\Enums\UnlockMode;
use App\Platform\Models\Achievement;
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
        (new UnlockPlayerAchievement())
            ->execute(
                $user,
                $achievement,
                $hardcoreUnlockTime !== null,
                $hardcoreUnlockTime ?? $softcoreUnlockTime,
            );

        // refresh user, unlocking achievements cascades into metrics recalculations
        $user->refresh();
    }

    protected function addHardcoreUnlock(User $user, Achievement $achievement, ?Carbon $when = null): void
    {
        $this->addPlayerAchievement($user, $achievement, $when ?? Carbon::now(), $when ?? Carbon::now());
    }

    protected function addSoftcoreUnlock(User $user, Achievement $achievement, ?Carbon $when = null): void
    {
        $this->addPlayerAchievement($user, $achievement, null, $when ?? Carbon::now());
    }

    protected function removeUnlock(User $user, Achievement $achievement): void
    {
        $user->playerAchievements()->where('achievement_id', $achievement->ID)->delete();
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
    }

    protected function getUnlockTime(User $user, Achievement $achievement, int $mode): ?Carbon
    {
        $unlock = $user->playerAchievements()->where('achievement_id', $achievement->ID)->first();

        return $mode === UnlockMode::Hardcore ? $unlock->unlocked_hardcore_at : $unlock->unlocked_at;
    }
}
