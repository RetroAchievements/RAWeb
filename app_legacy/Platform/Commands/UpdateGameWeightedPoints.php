<?php

declare(strict_types=1);

namespace LegacyApp\Platform\Commands;

use Illuminate\Console\Command;
use LegacyApp\Platform\Actions\UpdateGameWeightedPoints as UpdateGameWeightedPointsAction;
use LegacyApp\Platform\Models\Game;
use LegacyApp\Site\Models\StaticData;

class UpdateGameWeightedPoints extends Command
{
    protected $signature = 'ra-legacy:platform:update-game-weighted-points {gameId?}';
    protected $description = 'Calculate game weighted points (retro/true ratio)';

    public function __construct(
        private UpdateGameWeightedPointsAction $updateGameWeightedPointsAction,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $gameId = (int) $this->argument('gameId');
        if (!empty($gameId)) {
            $this->updateGameWeightedPointsAction->run($gameId);

            return;
        }

        $staticData = StaticData::first();

        $gameID = $staticData['NextGameToScan'];
        for ($i = 0; $i < 3; $i++) {
            $this->updateGameWeightedPointsAction->run($gameId);
            // get next highest game ID
            $gameID = Game::where('ID', '>', $gameID)->min('ID') ?? 1;
        }

        StaticData::first()->update([
            'NextGameToScan' => $gameID,
        ]);
    }
}
