<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Models\GameRelease;
use App\Platform\Enums\GameReleaseRegion;
use Illuminate\Console\Command;

class SyncGameReleases extends Command
{
    protected $signature = 'ra:sync:game-releases';
    protected $description = 'Syncs all GameData title and release date entries to the game_releases table';

    public function handle(): void
    {
        $this->info('Starting game releases sync...');

        $total = Game::count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Game::chunk(1000, function ($games) use ($bar) {
            foreach ($games as $game) {
                $exists = GameRelease::where('game_id', $game->id)
                    ->where('is_canonical_game_title', true)
                    ->exists();

                if (!$exists) {
                    GameRelease::create([
                        'game_id' => $game->id,

                        'title' => $game->title,

                        'released_at' => $game->released_at,
                        'released_at_granularity' => $game->released_at && $game->released_at_granularity
                                ? $game->released_at_granularity
                                : null,

                        'is_canonical_game_title' => true, // everything will initially be canonical
                        'region' => GameReleaseRegion::Worldwide,
                    ]);
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("Done.");
    }
}
