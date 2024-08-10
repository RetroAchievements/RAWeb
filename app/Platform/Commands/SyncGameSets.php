<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Models\GameAlternative;
use App\Models\GameSet;
use App\Models\GameSetGame;
use App\Models\GameSetLink;
use App\Models\System;
use App\Platform\Enums\GameSetType;
use Illuminate\Console\Command;

class SyncGameSets extends Command
{
    protected $signature = 'ra:sync:game-sets {--f|full} {--p|no-post}';
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

            if ($game->ConsoleID === System::Hubs) {
                GameSet::updateOrCreate(
                    ['game_id' => $game->id],
                    ['title' => $game->title, 'type' => GameSetType::HUB],
                );
            } else {
                GameSet::updateOrCreate(
                    ['game_id' => $game->id],
                    ['type' => GameSetType::GAME]
                );
            }

            $progressBar->advance();
        }
        $progressBar->finish();

        $gameAltsCount = GameAlternative::count();
        $this->info("\nPopulating {$gameAltsCount} game_set_games and game_set_links...");

        $progressBar = $this->output->createProgressBar($gameAltsCount);

        foreach (GameAlternative::cursor() as $gameAlt) {
            $parentGameSet = GameSet::firstWhere('game_id', $gameAlt->gameID);
            $game = Game::find($gameAlt->gameID);
            $altGame = Game::find($gameAlt->gameIDAlt);

            // Only process relationships in one direction so we avoid duplicate game_set_games and game_set_links.
            if ($gameAlt->gameID < $gameAlt->gameIDAlt) {
                if ($game->ConsoleID === System::Hubs || $altGame->ConsoleID === System::Hubs) {
                    $childGameSet = GameSet::firstWhere('game_id', $altGame->id);
                    GameSetLink::upsert([
                        'parent_game_set_id' => $parentGameSet->id,
                        'child_game_set_id' => $childGameSet->id,
                    ], uniqueBy: ['parent_game_set_id', 'child_game_set_id']);
                } else {
                    GameSetGame::upsert([
                        'game_set_id' => $parentGameSet->id,
                        'game_id' => $altGame->id,
                    ], uniqueBy: ['game_set_id', 'game_id']);
                }
            }

            $progressBar->advance();
        }
        $progressBar->finish();

        $this->info("\nCompleted populating game_set_games and game_set_links.");
    }
}
