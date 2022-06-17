<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Actions\UpdateGameMetricsAction;
use App\Platform\Models\Game;
use Illuminate\Console\Command;

class UpdateGameMetrics extends Command
{
    protected $signature = 'ra:server:game:update-metrics {game}';
    protected $description = "Update a game's metrics";

    public function __construct(private UpdateGameMetricsAction $updateGameMetricsAction)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        /** @var Game $game */
        $game = Game::findOrFail($this->argument('game'));

        $this->updateGameMetricsAction->execute($game);
    }
}
