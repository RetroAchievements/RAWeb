<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Platform\Actions\BackfillGameScreenshotsAction;
use App\Platform\Jobs\BackfillGameScreenshotsBatchJob;
use Illuminate\Bus\BatchRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class BackfillGameScreenshots extends Command
{
    protected $signature = 'ra:platform:game:backfill-screenshots
                            {gameId? : Process a single game by ID}
                            {--chunk-size=50 : Number of games per batch job}';
    protected $description = 'Backfill existing legacy screenshots into game_screenshots + MediaLibrary';

    public function handle(): void
    {
        $gameId = $this->argument('gameId');

        if ($gameId !== null) {
            $game = Game::with('system')->findOrFail($gameId);

            $this->info("Backfilling screenshots for game [{$game->id}:{$game->title}]...");

            (new BackfillGameScreenshotsAction())->execute($game);

            $this->info('Done.');

            return;
        }

        $query = Game::query()
            ->where(function ($q) {
                $q->where('image_ingame_asset_path', '!=', Game::PLACEHOLDER_IMAGE_PATH)
                    ->orWhere('image_title_asset_path', '!=', Game::PLACEHOLDER_IMAGE_PATH);
            })
            ->whereDoesntHave('gameScreenshots');

        $totalCount = $query->count();
        $this->info("Found {$totalCount} games to backfill.");

        if ($totalCount === 0) {
            return;
        }

        $chunkSize = (int) $this->option('chunk-size');
        $allGameIds = $query->pluck('id')->toArray();

        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->start();

        $jobs = [];
        $chunks = array_chunk($allGameIds, $chunkSize);

        foreach ($chunks as $chunk) {
            $jobs[] = new BackfillGameScreenshotsBatchJob($chunk);
            $progressBar->advance(count($chunk));
        }

        if (!empty($jobs)) {
            Bus::batch($jobs)
                ->name('backfill-game-screenshots')
                ->allowFailures()
                ->finally(function ($batch) {
                    if (!$batch->finished()) {
                        resolve(BatchRepository::class)->markAsFinished($batch->id);
                    }
                })
                ->dispatch();
        }

        $progressBar->finish();

        $this->newLine();
        $this->info('All jobs have been dispatched.');
    }
}
