<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Models\GameSet;
use App\Platform\Enums\GameSetType;
use Illuminate\Console\Command;

class SyncGameSetsInternalNotes extends Command
{
    protected $signature = 'ra:sync:game-sets:internal-notes';
    protected $description = 'Sync internal notes for hub-type game sets from their legacy games.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Starting internal notes sync for hub game sets...');

        // Get all hub game sets with non-null game_id.
        $gameSets = GameSet::whereType(GameSetType::Hub)
            ->whereNotNull('game_id')
            ->get();

        $count = $gameSets->count();
        $this->info("Found {$count} hub game sets to update.");

        $progressBar = $this->output->createProgressBar($count);

        foreach ($gameSets as $gameSet) {
            $game = Game::find($gameSet->game_id);

            if (!$game) {
                $this->warn("No game found for game_set {$gameSet->id}, skipping.");
                continue;
            }

            $internalNotes =
                ($game->Developer ? $game->Developer . ' ' : '') .
                ($game->Publisher ? $game->Publisher . ' ' : '') .
                ($game->Genre ? $game->Genre . ' ' : '');

            $gameSet->update([
                'internal_notes' => $internalNotes,
            ]);

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->info("\nCompleted syncing internal notes for hub game sets.");
    }
}
