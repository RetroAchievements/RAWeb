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
     * @return array{int, Collection<int, GameTopAchieverData>, int, int, int} array with number of masters, collection of top players, number of completions, number beaten, and number beaten softcore
     */
    public function execute(Game $game): array
    {
        $this->gameTopAchieversService->initialize($game);
        [$numMasters, $rawTopAchievers, $numCompletions, $numBeaten, $numBeatenSoftcore] =
            $this->gameTopAchieversService->getTopAchieversComponentData();

        /** @var array<int, array<string, mixed>> $rawTopAchievers */
        $topAchievers = collect($rawTopAchievers)
            ->map(function (array $topAchiever): GameTopAchieverData {
                return GameTopAchieverData::fromTopAchiever($topAchiever);
            });

        return [$numMasters, $topAchievers, $numCompletions, $numBeaten, $numBeatenSoftcore];
    }
}
