<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Platform\Actions\RegenerateGameScreenshotConversionsAction;
use App\Platform\Jobs\RegenerateGameScreenshotConversionsBatchJob;
use Illuminate\Bus\BatchRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Spatie\MediaLibrary\Conversions\ConversionCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class RegenerateGameScreenshotConversions extends Command
{
    protected $signature = 'ra:platform:game:regenerate-screenshot-conversions
                            {gameId? : Process a single game by ID}
                            {--chunk-size=20 : Number of media items per batch job}';
    protected $description = 'Re-run media conversions for existing game screenshots';

    public function handle(): void
    {
        $gameId = $this->argument('gameId');

        if ($gameId !== null) {
            $this->handleSingleGame((int) $gameId);

            return;
        }

        $this->handleAllGames();
    }

    private function handleSingleGame(int $gameId): void
    {
        $game = Game::findOrFail($gameId);

        $mediaItems = $game->getMedia('screenshots');

        if ($mediaItems->isEmpty()) {
            $this->info("No screenshot media found for game [{$game->id}:{$game->title}].");

            return;
        }

        $this->info("Regenerating conversions for {$mediaItems->count()} screenshots on game [{$game->id}:{$game->title}]...");

        $action = new RegenerateGameScreenshotConversionsAction();
        foreach ($mediaItems as $media) {
            $action->execute($media);
        }

        $this->info('Done.');
    }

    private function handleAllGames(): void
    {
        $baseQuery = Media::where('model_type', app(Game::class)->getMorphClass())
            ->where('collection_name', 'screenshots');

        $totalCount = $baseQuery->count();

        if ($totalCount === 0) {
            $this->info('No screenshot media found.');

            return;
        }

        // Resolve only the conversion names that apply to the screenshots collection.
        // In other words, don't queue up work if a game is missing a banner or something
        // else that's unrelated.
        $sampleMedia = (clone $baseQuery)->with('model')->first();
        $expectedConversions = ConversionCollection::createForMedia($sampleMedia)
            ->filter(fn ($conversion) => $conversion->shouldBePerformedOn('screenshots'))
            ->map(fn ($conversion) => $conversion->getName())
            ->values()
            ->all();

        $query = (clone $baseQuery)->where(function ($q) use ($expectedConversions) {
            foreach ($expectedConversions as $conversion) {
                $q->orWhereNull("generated_conversions->{$conversion}")
                    ->orWhere("generated_conversions->{$conversion}", false);
            }
        });

        $pendingCount = $query->count();
        $this->info("Found {$pendingCount} of {$totalCount} screenshot media items with pending conversions.");

        if ($pendingCount === 0) {
            return;
        }

        $chunkSize = (int) $this->option('chunk-size');
        $allMediaIds = $query->pluck('id')->toArray();

        $jobs = [];
        $chunks = array_chunk($allMediaIds, $chunkSize);

        foreach ($chunks as $chunk) {
            $jobs[] = new RegenerateGameScreenshotConversionsBatchJob($chunk);
        }

        if (!empty($jobs)) {
            Bus::batch($jobs)
                ->name('regenerate-game-screenshot-conversions')
                ->allowFailures()
                ->finally(function ($batch) {
                    if (!$batch->finished()) {
                        resolve(BatchRepository::class)->markAsFinished($batch->id);
                    }
                })
                ->dispatch();
        }

        $this->newLine();
        $this->info('All jobs have been dispatched.');
    }
}
