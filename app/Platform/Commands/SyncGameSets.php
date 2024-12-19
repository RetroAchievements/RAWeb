<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Models\GameAlternative;
use App\Models\GameSet;
use App\Models\GameSetGame;
use App\Models\GameSetLink;
use App\Models\System;
use App\Platform\Actions\UpdateGameSetFromGameAlternativesModificationAction;
use App\Platform\Enums\GameSetType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncGameSets extends Command
{
    protected $signature = 'ra:sync:game-sets';
    protected $description = 'Sync hubs to game_sets';

    private const STANDARD_HUBS = [
        '[Central]' => GameSet::CentralHubId,
        '[Central - Genre & Subgenre]' => GameSet::GenreSubgenreHubId,
        '[Central - Series]' => GameSet::SeriesHubId,
        '[Central - Community Events]' => GameSet::CommunityEventsHubId,
        '[Central - Developer Events]' => GameSet::DeveloperEventsHubId,
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Running a full GameAlternatives -> game_sets sync...');

        // This will be a full reset. Delete any existing game_sets data.
        // We'll use TRUNCATE to reset the auto-incrementing ID counter back to 1.
        $this->info("\nDeleting any existing game_sets data...");
        $this->wipeAllGameSetsData();
        $this->info("Deleted all existing game_sets data.");

        $this->info("\nCreating standard hub game_sets...");
        $this->createStandardHubs();
        $this->info('Created ' . count(self::STANDARD_HUBS) . ' standard hubs.');

        // Get all standard hub game IDs to exclude so we
        // don't accidentally try to recreate them.
        $standardHubGameIds = Game::where('ConsoleID', System::Hubs)
            ->whereIn('Title', array_keys(self::STANDARD_HUBS))
            ->pluck('id');

        // Loop through all GameAlternatives and create game_sets.
        $distinctGameIds = GameAlternative::select('gameID')
            ->whereNotIn('gameID', $standardHubGameIds)
            ->distinct()
            ->pluck('gameID');
        $distinctGameIdsCount = $distinctGameIds->count();

        $this->info("\nUpserting {$distinctGameIdsCount} game_sets derived from legacy GameAlternatives.");
        $progressBar = $this->output->createProgressBar($distinctGameIdsCount);
        foreach ($distinctGameIds as $gameId) {
            $game = Game::find($gameId);

            /** see UpdateGameSetFromGameAlternativesModificationAction::instantiateGameSetFromGame() */
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
            (new UpdateGameSetFromGameAlternativesModificationAction())->execute(
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

    private function createStandardHubs(): void
    {
        foreach (self::STANDARD_HUBS as $title => $id) {
            // Try to find the existing game.
            // This will return a result if we're using a production DB snapshot.
            $hubGame = Game::where('ConsoleID', System::Hubs)
                ->where('Title', $title)
                ->first();

            GameSet::unguard(); // temporarily allow filling the "id" field
            GameSet::create([
                'id' => $id,
                'title' => $title,
                'type' => GameSetType::Hub,
                'game_id' => $hubGame?->id,
            ]);
            GameSet::reguard();
        }
    }

    private function wipeAllGameSetsData(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        GameSetGame::truncate();
        GameSetLink::truncate();
        GameSet::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
