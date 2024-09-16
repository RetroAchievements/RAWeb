<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Concerns;

use App\Community\Enums\AwardType;
use App\Models\Game;
use App\Models\PlayerBadge;
use App\Models\User;
use App\Platform\Enums\UnlockMode;
use Carbon\Carbon;

trait TestsPlayerBadges
{
    protected function addPlayerBadge(
        User $user,
        int $type,
        int $id,
        int $extra = 0,
        ?Carbon $awardTime = null
    ): void {
        if ($awardTime === null) {
            $awardTime = Carbon::now();
        }

        $badge = $user->playerBadges()
            ->where('AwardType', '=', $type)
            ->where('AwardData', '=', $id)
            ->where('AwardDataExtra', '=', $extra)
            ->first();
        if ($badge === null) {
            $badge = new PlayerBadge([
                'user_id' => $user->id,
                'AwardType' => $type,
                'AwardData' => $id,
                'AwardDataExtra' => $extra,
                'AwardDate' => $awardTime,
            ]);
            $user->playerBadges()->save($badge);
        }
    }

    protected function addGameBeatenAward(
        User $user,
        Game $game,
        int $mode = UnlockMode::Hardcore,
        ?Carbon $awardTime = null
    ): void {
        $this->addPlayerBadge($user, AwardType::GameBeaten, $game->ID, $mode, $awardTime);
    }

    protected function beatenBadgeExists(User $user, Game $game, ?int $mode): bool
    {
        $badge = $user->playerBadges()
            ->where('AwardType', AwardType::GameBeaten)
            ->where('AwardData', $game->ID);

            if ($mode !== null) {
            $badge = $badge->where('AwardDataExtra', UnlockMode::Hardcore);
        }

        return $badge->exists();
    }

    protected function assertHasBeatenBadge(User $user, Game $game, ?int $mode = null): void
    {
        $this->assertTrue(
            $this->beatenBadgeExists($user, $game, $mode),
            "No beaten badge for game " . $game->ID . "/user " . $user->ID,
        );
    }

    protected function assertDoesNotHaveBeatenBadge(User $user, Game $game, ?int $mode = null): void
    {
        $this->assertFalse(
            $this->beatenBadgeExists($user, $game, $mode),
            "Found beaten badge for game " . $game->ID . "/user " . $user->ID,
        );
    }

    protected function addMasteryBadge(
        User $user,
        Game $game,
        int $mode = UnlockMode::Hardcore,
        ?Carbon $awardTime = null
    ): void {
        $this->addPlayerBadge($user, AwardType::Mastery, $game->ID, $mode, $awardTime);
    }

    protected function masteryBadgeExists(User $user, Game $game): bool
    {
        return $user->playerBadges()
            ->where('AwardType', AwardType::Mastery)
            ->where('AwardData', $game->ID)
            ->where('AwardDataExtra', UnlockMode::Hardcore)
            ->exists();
    }

    protected function assertHasMasteryBadge(User $user, Game $game): void
    {
        $this->assertTrue(
            $this->masteryBadgeExists($user, $game),
            "No mastery badge for game " . $game->ID . "/user " . $user->ID,
        );
    }

    protected function assertDoesNotHaveMasteryBadge(User $user, Game $game): void
    {
        $this->assertFalse(
            $this->masteryBadgeExists($user, $game),
            "Found mastery badge for game " . $game->ID . "/user " . $user->ID,
        );
    }

    protected function completionBadgeExists(User $user, Game $game): bool
    {
        return $user->playerBadges()
            ->where('AwardType', AwardType::Mastery)
            ->where('AwardData', $game->ID)
            ->where('AwardDataExtra', UnlockMode::Softcore)
            ->exists();
    }

    protected function assertHasCompletionBadge(User $user, Game $game): void
    {
        $this->assertTrue(
            $this->completionBadgeExists($user, $game),
            "No completion badge for game " . $game->ID . "/user " . $user->ID,
        );
    }

    protected function assertDoesNotHaveCompletionBadge(User $user, Game $game): void
    {
        $this->assertFalse(
            $this->completionBadgeExists($user, $game),
            "Found completion badge for game " . $game->ID . "/user " . $user->ID,
        );
    }
}
