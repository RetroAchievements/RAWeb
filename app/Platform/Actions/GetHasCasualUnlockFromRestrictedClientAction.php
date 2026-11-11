<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\PlayerAchievement;
use App\Models\PlayerSession;
use App\Platform\Services\UserAgentService;

class GetHasCasualUnlockFromRestrictedClientAction
{
    /**
     * Hardcore unlocks from restricted emulators/cores are automatically
     * demoted to casual. We don't want to offer the manual unlock option
     * for users who find themselves in this circumstance.
     */
    public function execute(?PlayerAchievement $playerAchievement, Achievement $achievement): bool
    {
        if (
            !$playerAchievement?->unlocked_at
            || $playerAchievement->unlocked_hardcore_at
            || !$playerAchievement->player_session_id
        ) {
            return false;
        }

        $userAgentService = new UserAgentService();

        $unlockUserAgent = PlayerSession::whereKey($playerAchievement->player_session_id)->value('user_agent');
        if (!$unlockUserAgent || $userAgentService->getSupportLevel($unlockUserAgent)->allowsHardcoreUnlocks()) {
            return false;
        }

        // Some players have thousands of sessions per game.
        // Only check their newest sessions.
        foreach ($achievement->getRelatedGameIds() as $gameId) {
            $laterUserAgents = PlayerSession::where('user_id', $playerAchievement->user_id)
                ->where('game_id', $gameId)
                ->where('rich_presence_updated_at', '>=', $playerAchievement->unlocked_at)
                ->orderByDesc('rich_presence_updated_at')
                ->limit(100)
                ->pluck('user_agent')
                ->filter()
                ->unique();

            foreach ($laterUserAgents as $userAgent) {
                if ($userAgentService->getSupportLevel($userAgent)->allowsHardcoreUnlocks()) {
                    return false;
                }
            }
        }

        return true;
    }
}
