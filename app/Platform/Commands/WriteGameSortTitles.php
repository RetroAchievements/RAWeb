<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Platform\Actions\WriteGameSortTitleFromGameTitleAction;
use Illuminate\Console\Command;

class WriteGameSortTitles extends Command
{
    protected $signature = "ra:platform:write-game-sort-titles
                            {gameId? : Target a single game}";
    protected $description = 'Write sort titles for games derived from their canonical titles';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $gameId = $this->argument('gameId');
        if ($gameId !== null) {
            $game = Game::findOrFail($gameId);

            $this->info("\nUpserting a sort title for [{$game->id}:{$game->title}].");

            (new WriteGameSortTitleFromGameTitleAction())->execute(
                $game,
                $game->title,
                shouldRespectCustomSortTitle: false,
            );

            $this->info('Done.');
        } else {
            $gamesCount = Game::count();
            $this->info("\nUpserting sort titles for {$gamesCount} games.");

            $progressBar = $this->output->createProgressBar($gamesCount);
            $progressBar->start();

            $action = new WriteGameSortTitleFromGameTitleAction();
            Game::query()->chunk(100, function ($games) use ($progressBar, $action) {
                foreach ($games as $game) {
                    $action->execute($game, $game->title, shouldRespectCustomSortTitle: false);
                }

                $progressBar->advance(count($games));
            });

            $progressBar->finish();

            $this->info("\nAll sort titles have been upserted.");
        }
    }
}
