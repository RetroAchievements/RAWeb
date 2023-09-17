<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Concerns;

use App\Community\Enums\AwardType;
use App\Platform\Enums\UnlockMode;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerBadge;
use App\Site\Models\User;
use Carbon\Carbon;

trait TestsPlayerBadges
{
    protected function addPlayerBadge(User $user, int $type, int $id, int $extra = 0, ?Carbon $awardTime = null): void
    {
        if ($awardTime === null) {
            $awardTime = Carbon::now();
        }

        $badge = $user->playerBadges()
            ->where('AwardType', '=', $type)
            ->where('AwardData', '=', $id)
            ->first();
        if ($badge === null) {
            $badge = new PlayerBadge([
                'User' => $user->User,
                'AwardType' => $type,
                'AwardData' => $id,
                'AwardDataExtra' => $extra,
                'AwardDate' => $awardTime,
            ]);
            $user->playerBadges()->save($badge);
        }
    }

    protected function addMasteryBadge(User $user, Game $game, int $mode = UnlockMode::Hardcore, ?Carbon $awardTime = null): void
    {
        $this->addPlayerBadge($user, AwardType::Mastery, $game->ID, $mode, $awardTime);
    }

    protected function masteryBadgeExists(User $user, Game $game): bool
    {
        return $user->playerBadges()->where('AwardType', AwardType::Mastery)->where('AwardData', $game->ID)->exists();
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
}
