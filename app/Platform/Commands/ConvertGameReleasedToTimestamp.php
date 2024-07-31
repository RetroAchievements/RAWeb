<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Platform\Actions\ConvertGameReleasedToTimestamp as ConvertGameReleasedToTimestampAction;
use Illuminate\Console\Command;

class ConvertGameReleasedToTimestamp extends Command
{
    protected $signature = "ra:platform:game:convert-released-to-timestamp
                            {gameId? : Target a single game}";
    protected $description = "Migrate game `Released` string values to `released_at` timestamp values";

    public function __construct(
        protected ConvertGameReleasedToTimestampAction $convertGameReleasedToTimestampAction
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $gameId = $this->argument('gameId');
        if ($gameId !== null) {
            $game = Game::findOrFail($gameId);

            [$timestamp, $granularity] = $this->convertGameReleasedToTimestampAction->execute($game);

            if ($timestamp) {
                $this->info("Wrote {$timestamp}:{$granularity} for [{$game->id}:{$game->title}].");
            } else {
                $this->info("Could not write a timestamp for [{$game->id}:{$game->title}].");
            }
        } else {
            $gamesBaseQuery = Game::select('ID', 'Released', 'Title')
                ->whereNotNull('Released')
                ->where('Released', '!=', '');

            $gamesCount = $gamesBaseQuery->count();

            $this->info('Updating released_at timestamp for ' . $gamesCount . ' games.');
            $progressBar = $this->output->createProgressBar($gamesCount);

            $games = $gamesBaseQuery->get();
            foreach ($games as $game) {
                $timestamp = $this->convertGameReleasedToTimestampAction->execute($game);

                if ($timestamp) {
                    // TODO enable this if you need it for debugging purposes.
                    // $this->info("Wrote {$timestamp} for [{$game->id}:{$game->title}].");
                }

                $progressBar->advance();
            }

            $progressBar->finish();

            $this->info("Done.");
        }
    }
}
