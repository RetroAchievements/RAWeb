<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Models\GameTitle;
use App\Platform\Enums\GameTitleRegion;
use Illuminate\Console\Command;

class SyncGameTitles extends Command
{
    protected $signature = 'ra:sync:game-titles';
    protected $description = 'Syncs all GameData.Title entries to the game_titles table as canonical titles';

    public function handle(): void
    {
        $this->info('Starting game title sync...');

        $total = Game::count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Game::chunk(1000, function ($games) use ($bar) {
            foreach ($games as $game) {
                $exists = GameTitle::where('game_id', $game->id)
                    ->where('is_canonical', true)
                    ->exists();

                if (!$exists) {
                    GameTitle::create([
                        'game_id' => $game->id,
                        'title' => $game->title,
                        'is_canonical' => true,
                        'region' => GameTitleRegion::Worldwide,
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
