<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Actions\UpdateGameWeightedPoints as UpdateGameWeightedPointsAction;
use App\Platform\Models\Game;
use App\Site\Models\StaticData;
use Illuminate\Console\Command;

class UpdateGameWeightedPoints extends Command
{
    protected $signature = 'ra:platform:update-game-weighted-points {gameId?}';
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

        $gameId = $staticData['NextGameToScan'];
        for ($i = 0; $i < 3; $i++) {
            $this->updateGameWeightedPointsAction->run($gameId);
            // get next highest game ID
            $gameId = Game::where('ID', '>', $gameId)->min('ID') ?? 1;
        }

        StaticData::first()->update([
            'NextGameToScan' => $gameId,
        ]);
    }
}
