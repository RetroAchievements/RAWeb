<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Platform\Actions\SyncAchievementSetImageAssetPathFromGameAction;
use Illuminate\Console\Command;

class SyncAchievementSetImageAssetPaths extends Command
{
    protected $signature = "ra:sync:achievement-set-image-asset-paths
                            {gameId? : Target a single game}";
    protected $description = 'Sync icon images for achievement sets';

    public function __construct(
        protected SyncAchievementSetImageAssetPathFromGameAction $action
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $gameId = $this->argument('gameId');
        if ($gameId !== null) {
            $game = Game::findOrFail($gameId);

            $this->info("\nUpserting image_asset_path for [{$game->id}:{$game->title}].");

            $this->action->execute($game);

            $this->info('Done.');
        } else {
            $baseGamesQuery = Game::has('gameAchievementSets');

            $count = $baseGamesQuery->count();
            $this->info("\nUpserting image_asset_path values for {$count} games.");

            $progressBar = $this->output->createProgressBar($count);
            $progressBar->start();

            $baseGamesQuery->chunk(100, function ($games) use ($progressBar) {
                foreach ($games as $game) {
                    $this->action->execute($game);
                }

                $progressBar->advance(count($games));
            });

            $progressBar->finish();

            $this->info("\nDone.");
        }
    }
}
