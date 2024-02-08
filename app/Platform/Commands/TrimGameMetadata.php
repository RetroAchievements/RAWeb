<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Platform\Actions\TrimGameMetadata as TrimGameMetadataAction;
use Illuminate\Console\Command;

class TrimGameMetadata extends Command
{
    protected $signature = 'ra:platform:game:trim-metadata
                            {gameIds? : Comma-separated list of game IDs. Leave empty to update all games}';
    protected $description = "Trim whitespace from game(s) metadata";

    public function __construct(
        private readonly TrimGameMetadataAction $trimGameMetadata
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $gameIds = collect(explode(',', $this->argument('gameIds') ?? ''))
            ->filter()
            ->map(fn ($id) => (int) $id);

        $gamesToUpdate = $gameIds->isNotEmpty() ? Game::whereIn('ID', $gameIds)->get() : Game::all();

        $progressBar = $this->output->createProgressBar($gamesToUpdate->count());
        $progressBar->start();
        $updatedCount = 0;

        foreach ($gamesToUpdate as $game) {
            $this->trimGameMetadata->execute($game);
            $progressBar->advance();
            $updatedCount++;
        }

        $progressBar->finish();
        $this->info("Updated metadata for {$updatedCount} games.");
        $this->line(PHP_EOL);
    }
}
