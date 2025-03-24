<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Platform\Data\GameTopAchieverData;
use App\Platform\Services\GameTopAchieversService;
use Illuminate\Support\Collection;

class LoadGameTopAchieversAction
{
    public function __construct(
        protected GameTopAchieversService $gameTopAchieversService,
    ) {
    }

    /**
     * Get the top players, either by points or by most recent
     * mastery, for a given game.
     *
     * @param Game $game the target game to load top players for
     * @return array{int, Collection<int, GameTopAchieverData>} array with number of masters and collection of top players
     */
    public function execute(Game $game): array
    {
        $this->gameTopAchieversService->initialize($game);
        [$numMasters, $rawTopAchievers] = $this->gameTopAchieversService->getTopAchieversComponentData();

        /** @var array<int, array<string, mixed>> $rawTopAchievers */
        $topAchievers = collect($rawTopAchievers)
            ->map(function (array $topAchiever): GameTopAchieverData {
                return GameTopAchieverData::fromTopAchiever($topAchiever);
            });

        return [$numMasters, $topAchievers];
    }
}
