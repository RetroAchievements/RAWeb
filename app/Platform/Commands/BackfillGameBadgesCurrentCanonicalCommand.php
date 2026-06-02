<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Models\System;
use App\Platform\Services\GameBadgeBackfillService;
use Illuminate\Console\Command;

class BackfillGameBadgesCurrentCanonicalCommand extends Command
{
    protected $signature = 'ra:platform:game-badges:backfill-current-canonical';
    protected $description = 'Ensure every game with a non-placeholder badge has a current game_badges row';

    public function handle(GameBadgeBackfillService $backfillService): void
    {
        $this->info('Building media file index...');
        $backfillService->buildFileIndex();

        $query = Game::query()
            ->whereNotIn('system_id', System::getNonGameSystems())
            ->orderBy('id');

        $count = $query->count();
        $this->info("Reconciling current canonical badges for {$count} games...");

        $progressBar = $this->output->createProgressBar($count);

        $query->chunkById(500, function ($games) use ($backfillService, $progressBar): void {
            foreach ($games as $game) {
                /** @var Game $game */
                $backfillService->reconcileCurrentCanonical($game);
                $progressBar->advance();
            }
        });

        $progressBar->finish();

        $this->newLine();
        $this->info('Current canonical backfill complete.');
    }
}
