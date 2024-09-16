<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlags;
use Illuminate\Console\Command;

class SyncGameAchievementSets extends Command
{
    protected $signature = "ra:sync:game-achievement-sets
                            {gameId? : Target a single game}";
    protected $description = 'Sync games and their attached achievements to formal sets';

    public function __construct(
        protected UpsertGameCoreAchievementSetFromLegacyFlags $upsertGameCoreAchievementSetFromLegacyFlags
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $gameId = $this->argument('gameId');
        if ($gameId !== null) {
            $game = Game::findOrFail($gameId);

            $this->info("\nUpserting a formal set for [{$game->id}:{$game->title}].");

            $this->upsertGameCoreAchievementSetFromLegacyFlags->execute($game);

            $this->info('Done.');
        } else {
            $baseGamesQuery = Game::where('achievements_published', '>', 0)
                ->orWhere('achievements_unpublished', '>', 0);

            $gamesWithAchievementSetsCount = $baseGamesQuery->count();
            $this->info("\nUpserting game achievement sets for {$gamesWithAchievementSetsCount} games.");

            $progressBar = $this->output->createProgressBar($gamesWithAchievementSetsCount);
            $progressBar->start();

            $baseGamesQuery->chunk(100, function ($games) use ($progressBar) {
                foreach ($games as $game) {
                    $this->upsertGameCoreAchievementSetFromLegacyFlags->execute($game);
                }

                $progressBar->advance(count($games));
            });

            $progressBar->finish();

            $this->info("\nAll achievement sets have been upserted.");
        }
    }
}
