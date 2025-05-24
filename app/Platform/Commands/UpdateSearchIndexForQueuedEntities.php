<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Achievement;
use App\Models\Game;
use App\Platform\Services\SearchIndexingService;
use Illuminate\Console\Command;

class UpdateSearchIndexForQueuedEntities extends Command
{
    protected $signature = 'ra:search:update-queued-entities';
    protected $description = 'Update Scout search index for queued entities';

    public function __construct(
        protected readonly SearchIndexingService $searchIndexingService
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->updateGameIndex();
        $this->updateAchievementIndex();
    }

    private function updateGameIndex(): void
    {
        $pendingGameIds = $this->searchIndexingService->getPendingGames();

        if (empty($pendingGameIds)) {
            $this->info('No games pending search index update.');

            return;
        }

        $this->info('Updating search index for ' . count($pendingGameIds) . ' games...');

        $chunks = array_chunk($pendingGameIds, 100);
        foreach ($chunks as $chunkIds) {
            $games = Game::whereIn('ID', $chunkIds)->get();
            $games->searchable(); // this forces Scout to update the search index
        }

        $this->searchIndexingService->clearPendingGames();

        $this->info('Game search index update completed.');
    }

    private function updateAchievementIndex(): void
    {
        $pendingAchievementIds = $this->searchIndexingService->getPendingAchievements();

        if (empty($pendingAchievementIds)) {
            $this->info('No achievements pending search index update.');

            return;
        }

        $this->info('Updating search index for ' . count($pendingAchievementIds) . ' achievements...');

        $chunks = array_chunk($pendingAchievementIds, 100);
        foreach ($chunks as $chunkIds) {
            $achievements = Achievement::whereIn('ID', $chunkIds)->get();
            $achievements->searchable(); // this forces Scout to update the search index
        }

        $this->searchIndexingService->clearPendingAchievements();

        $this->info('Achievement search index update completed.');
    }
}
