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

            if ($game->ConsoleID === System::Hubs) {
                GameSet::updateOrCreate(
                    ['game_id' => $game->id],
                    ['type' => GameSetType::HUB, 'title' => $game->title, 'image_asset_path' => $game->ImageIcon],
                );
            } else {
                GameSet::updateOrCreate(
                    ['game_id' => $game->id],
                    ['type' => GameSetType::SIMILAR_GAMES, 'title' => 'Similar Games', 'image_asset_path' => null],
                );
            }

            $progressBar->advance();
        }
        $progressBar->finish();

        $gameAltsCount = GameAlternative::count();
        $this->info("\nPopulating {$gameAltsCount} game_set_games and game_set_links...");

        $progressBar = $this->output->createProgressBar($gameAltsCount);

        foreach (GameAlternative::cursor() as $gameAlt) {
            $parentGame = Game::find($gameAlt->gameID);
            $childGame = Game::find($gameAlt->gameIDAlt);

            // Determine if a swap is needed to ensure the hub is always the parent.
            if (
                ($parentGame->ConsoleID !== System::Hubs && $childGame->ConsoleID === System::Hubs)
                || ($gameAlt->gameID > $gameAlt->gameIDAlt)
            ) {
                // Swap to ensure the hub is the parent, or ensure consistent ordering.
                $temp = $parentGame;
                $parentGame = $childGame;
                $childGame = $temp;
            }

            // Ensure the hub is always the parent in the relationship.
            if ($childGame->ConsoleID === System::Hubs && $parentGame->ConsoleID !== System::Hubs) {
                // Swap the parent and child so that the hub is always the parent.
                $temp = $parentGame;
                $parentGame = $childGame;
                $childGame = $temp;
            }

            $parentGameSet = GameSet::firstWhere('game_id', $parentGame->id);
            $childGameSet = GameSet::firstWhere('game_id', $childGame->id);

            if ($parentGame->ConsoleID === System::Hubs && $childGame->ConsoleID === System::Hubs) {
                GameSetLink::upsert([
                    'parent_game_set_id' => $parentGameSet->id,
                    'child_game_set_id' => $childGameSet->id,
                    'created_at' => $childGame->Created,
                    'updated_at' => $childGame->Updated,
                ], uniqueBy: ['parent_game_set_id', 'child_game_set_id']);
            } else {
                GameSetGame::upsert([
                    'game_set_id' => $parentGameSet->id,
                    'game_id' => $childGame->id,
                    'created_at' => $childGame->Created,
                    'updated_at' => $childGame->Updated,
                ], uniqueBy: ['game_set_id', 'game_id']);

                // Ensure bi-directionality for non-hub games.
                GameSetGame::upsert([
                    'game_set_id' => $childGameSet->id,
                    'game_id' => $parentGame->id,
                    'created_at' => $parentGame->Created,
                    'updated_at' => $parentGame->Updated,
                ], uniqueBy: ['game_set_id', 'game_id']);
            }

            $progressBar->advance();
        }
        $progressBar->finish();

        $this->info("\nCompleted populating game_set_games and game_set_links.");
    }
}
