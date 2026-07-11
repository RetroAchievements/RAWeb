<?php

declare(strict_types=1);

namespace App\Api\V2\Actions;

use App\Models\Game;
use App\Models\User;
use App\Platform\Enums\UnlockMode;

class BuildGameAchievementDistributionHistogramAction
{
    /**
     * @return array{
     *   promoted: array{totalAchievements: int, distribution: array<int, array{unlockCount: int, playersHardcore: int, playersCasual: int}>},
     *   unpromoted: array{totalAchievements: int, distribution: array<int, array{unlockCount: int, playersHardcore: int, playersCasual: int}>},
     * }
     */
    public function execute(Game $game, ?User $requestedBy): array
    {
        return [
            'promoted' => $this->buildGroup($game, $requestedBy, isPromoted: true),
            'unpromoted' => $this->buildGroup($game, $requestedBy, isPromoted: false),
        ];
    }

    /**
     * @return array{totalAchievements: int, distribution: array<int, array{unlockCount: int, playersHardcore: int, playersCasual: int}>}
     */
    private function buildGroup(Game $game, ?User $requestedBy, bool $isPromoted): array
    {
        $numPlayers = $game->players_total ?? 0;

        $casual = getAchievementDistribution(
            $game->id,
            UnlockMode::Casual,
            $requestedBy?->username,
            $isPromoted,
            $numPlayers
        );
        $hardcore = getAchievementDistribution(
            $game->id,
            UnlockMode::Hardcore,
            $requestedBy?->username,
            $isPromoted,
            $numPlayers
        );

        $distribution = [];
        foreach (array_keys($casual) as $unlockCount) {
            $distribution[] = [
                'unlockCount' => $unlockCount,
                'playersHardcore' => $hardcore[$unlockCount] ?? 0,
                'playersCasual' => $casual[$unlockCount] ?? 0,
            ];
        }

        return [
            'totalAchievements' => count($distribution),
            'distribution' => $distribution,
        ];
    }
}
