<?php

declare(strict_types=1);

namespace App\Platform\Listeners;

use App\Models\GameScreenshot;
use App\Platform\Actions\RevalidateMediaContributionBadgeEligibilityAction;
use App\Platform\Events\AchievementCreated;
use App\Platform\Events\AchievementMoved;
use App\Platform\Events\AchievementPromoted;
use Illuminate\Contracts\Queue\ShouldQueue;

class RevalidateMediaContributionBadgeEligibility implements ShouldQueue
{
    public function handle(AchievementCreated|AchievementMoved|AchievementPromoted $event): void
    {
        $developer = $event->achievement->developer;
        if (!$developer || $developer->trashed()) {
            return;
        }

        $gameIds = [$event->achievement->game_id];
        if ($event instanceof AchievementMoved) {
            $gameIds[] = $event->originalGame->id;
        }

        $hasScreenshotOnGame = GameScreenshot::query()
            ->where('captured_by_user_id', $developer->id)
            ->whereIn('game_id', array_unique($gameIds))
            ->countsTowardMediaContributionStatus()
            ->exists();
        if (!$hasScreenshotOnGame) {
            return;
        }

        (new RevalidateMediaContributionBadgeEligibilityAction())->execute($developer);
    }
}
