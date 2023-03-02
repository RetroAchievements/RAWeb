<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use LegacyApp\Site\Models\User;
use LegacyApp\Community\Enums\AwardType;
use LegacyApp\Platform\Enums\UnlockMode;
use LegacyApp\Platform\Models\Game;
use LegacyApp\Platform\Models\PlayerBadge;

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

    protected function findMasteryBadge(User $user, Game $game): ?PlayerBadge
    {
        return $user->playerBadges()->where('AwardType', AwardType::Mastery)->where('AwardData', $game->ID)->first();
    }

    protected function assertHasMasteryBadge(User $user, Game $game): void
    {
        $badge = $this->findMasteryBadge($user, $game);
        if ($badge === null) {
            $this->fail("No mastery badge for game " . $game->ID . "/user " . $user->ID);
        }
    }

    protected function assertDoesNotHaveMasteryBadge(User $user, Game $game): void
    {
        $badge = $this->findMasteryBadge($user, $game);
        if ($badge !== null) {
            $this->fail("Found mastery badge for game " . $game->ID . "/user " . $user->ID);
        }
    }
}
