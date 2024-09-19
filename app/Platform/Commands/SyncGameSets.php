<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Models\GameAlternative;
use App\Models\GameSet;
use App\Models\GameSetGame;
use App\Models\GameSetLink;
use App\Models\System;
use App\Platform\Actions\UpdateGameSetFromGameAlternativesModification;
use App\Platform\Enums\GameSetType;
use Illuminate\Console\Command;

class SyncGameSets extends Command
{
    protected $signature = 'ra:sync:game-sets';
    protected $description = 'Sync hubs to game_sets';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Running a full GameAlternatives -> game_sets sync...');

        // This will be a full reset. Delete any existing game_sets data.
        $this->info("\nDeleting any existing game_sets data...");
        GameSetLink::query()->forceDelete();
        GameSetGame::query()->forceDelete();
        GameSet::query()->forceDelete();
        $this->info("Deleted all existing game_sets data.");

        // Loop through all GameAlternatives and create game_sets.
        $distinctGameIds = GameAlternative::select('gameID')->distinct()->pluck('gameID');
        $distinctGameIdsCount = $distinctGameIds->count();

        $this->info("\nUpserting {$distinctGameIdsCount} game_sets derived from legacy GameAlternatives.");
        $progressBar = $this->output->createProgressBar($distinctGameIdsCount);
        foreach ($distinctGameIds as $gameId) {
            $game = Game::find($gameId);

            /** see UpdateGameSetFromGameAlternativesModification::instantiateGameSetFromGame() */
            $isGameHub = $game->ConsoleID === System::Hubs;
            GameSet::updateOrCreate(
                ['game_id' => $game->id],
                [
                    'title' => $isGameHub ? $game->title : 'Similar Games',
                    'type' => $isGameHub ? GameSetType::Hub : GameSetType::SimilarGames,
                    'image_asset_path' => $isGameHub ? $game->ImageIcon : null,
                ]
            );

            $progressBar->advance();
        }
        $progressBar->finish();

        $gameAltsCount = GameAlternative::count();
        $this->info("\nPopulating {$gameAltsCount} game_set_games and game_set_links...");

        $progressBar = $this->output->createProgressBar($gameAltsCount);

        foreach (GameAlternative::cursor() as $gameAlt) {
            (new UpdateGameSetFromGameAlternativesModification())->execute(
                parentGameId: $gameAlt->gameID,
                childGameId: $gameAlt->gameIDAlt,
                isAttaching: true,
                existingGameAlt: $gameAlt,
            );

            $progressBar->advance();
        }
        $progressBar->finish();

        $this->info("\nCompleted populating game_set_games and game_set_links.");
    }
}
