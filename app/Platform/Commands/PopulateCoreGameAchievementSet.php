<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Platform\Actions\PopulateCoreGameAchievementSet as PopulateCoreGameAchievementSetAction;
use App\Platform\Jobs\PopulateCoreGameAchievementSetJob;
use Illuminate\Console\Command;

class PopulateCoreGameAchievementSet extends Command
{
    protected $signature = 'ra:platform:game:populate-core-set
                            {gameId? : Game ID to populate core set for}';
    protected $description = 'Populate game achievement core set(s). If a game ID is not given, all core sets will be populated.';

    public function __construct(
        private readonly PopulateCoreGameAchievementSetAction $populateCoreGameAchievementSet
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $gameId = $this->argument('gameId');

        if ($gameId !== null) {
            $game = Game::findOrFail($gameId);

            $this->info('Updating set data for game [' . $game->id . ':' . $game->Title . ']');

            $this->populateCoreGameAchievementSet->execute($game);
        } else {
            if ($this->confirm('This is a potentially destructive action if it has already run once. Existing set data will be dropped. Are you sure?')) {
                // We want to dispatch unique jobs for all game IDs on the
                // GameData table that have any achievements associated with them.
                $numGamesWithSets = Game::where('achievements_published', '>', 0)
                    ->orWhere('achievements_unpublished', '>', 0)
                    ->count();

                $this->info('Preparing batch jobs to populate core sets for ' . $numGamesWithSets . ' games.');

                $progressBar = $this->output->createProgressBar($numGamesWithSets);
                $progressBar->start();

                Game::where('achievements_published', '>', 0)
                ->orWhere('achievements_unpublished', '>', 0)
                ->each(function ($game) use ($progressBar) {
                    dispatch(new PopulateCoreGameAchievementSetJob($game->id))
                        ->onQueue('core-game-achievement-sets');

                    $progressBar->advance();
                }, 100);

                $progressBar->finish();

                $this->info('All jobs have been dispatched.');
            }
        }
    }
}
