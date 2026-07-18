<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Models\System;
use App\Platform\Services\GameBadgeBackfillService;
use Illuminate\Console\Command;

class BackfillGameBadgesCollapseSameDayCommand extends Command
{
    protected $signature = 'ra:platform:game-badges:collapse-same-day';
    protected $description = 'Collapse same-day badge rows per game. Keep only the latest upload of each day.';

    public function handle(GameBadgeBackfillService $backfillService): void
    {
        $query = Game::query()
            ->whereNotIn('system_id', System::getNonGameSystems())
            ->orderBy('id');

        $count = (int) $query->count();
        $this->info("Collapsing same-day rows for {$count} games...");

        $progressBar = $this->output->createProgressBar($count);
        $totalDeleted = 0;
        $gamesAffected = 0;

        $query->chunkById(500, function ($games) use ($backfillService, $progressBar, &$totalDeleted, &$gamesAffected): void {
            foreach ($games as $game) {
                /** @var Game $game */
                $deleted = $backfillService->collapseSameDayTransitions($game->id);

                if ($deleted > 0) {
                    $totalDeleted += $deleted;
                    $gamesAffected++;
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine();
        $this->info(sprintf(
            'Same-day collapse complete. Deleted %d rows across %d games.',
            $totalDeleted,
            $gamesAffected,
        ));
    }
}
