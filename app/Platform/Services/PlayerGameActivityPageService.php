<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\Game;
use App\Models\User;

class PlayerGameActivityPageService
{
    public function __construct(
        protected PlayerGameActivityService $playerGameActivityService,
        protected UserAgentService $userAgentService,
    ) {
    }

    public function buildViewData(User $user, Game $game): array
    {
        $this->playerGameActivityService->initialize($user, $game);
        $summary = $this->playerGameActivityService->summarize();

        $estimated = ($summary['generatedSessionAdjustment'] !== 0) ? " (estimated)" : "";

        $unlockSessionCount = $summary['achievementSessionCount'];
        $sessionInfo = "$unlockSessionCount session";
        if ($unlockSessionCount != 1) {
            $sessionInfo .= 's';

            if ($unlockSessionCount > 1) {
                $elapsedAchievementDays = ceil($summary['totalUnlockTime'] / (24 * 60 * 60));
                if ($elapsedAchievementDays > 2) {
                    $sessionInfo .= " over $elapsedAchievementDays days";
                } else {
                    $sessionInfo .= " over " . ceil($summary['totalUnlockTime'] / (60 * 60)) . " hours";
                }
            }
        }

        $gameAchievementCount = $game->achievements_published ?? 0;
        $userProgress = ($gameAchievementCount > 0) ? sprintf("/%d (%01.2f%%)",
            $gameAchievementCount, $this->playerGameActivityService->achievementsUnlocked * 100 / $gameAchievementCount) : "n/a";

        return [
            'activity' => $this->playerGameActivityService,
            'estimated' => $estimated,
            'sessionInfo' => $sessionInfo,
            'summary' => $summary,
            'userAgentService' => $this->userAgentService,
            'userProgress' => $userProgress,
        ];
    }
}
