<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Models\GameSet;
use App\Models\System;
use App\Platform\Enums\GameSetType;
use Illuminate\Console\Command;

class SyncGameSetsHubForumTopicIds extends Command
{
    protected $signature = 'ra:sync:game-sets:hub-forum-topic-ids';
    protected $description = 'Sync forum topic IDs from GameData to game_sets table for hubs.';

    public function handle(): void
    {
        $legacyHubRecords = Game::where('ConsoleID', System::Hubs)
            ->whereNotNull('ForumTopicID')
            ->select('ID', 'ForumTopicID')
            ->get();

        $totalLegacyHubs = $legacyHubRecords->count();
        $this->info("Syncing {$totalLegacyHubs} legacy hubs with forum topic ID values.");

        $progressBar = $this->output->createProgressBar($totalLegacyHubs);
        $progressBar->start();

        foreach ($legacyHubRecords as $legacyHub) {
            GameSet::whereType(GameSetType::Hub)
                ->whereGameId($legacyHub->id)
                ->update(['forum_topic_id' => $legacyHub->ForumTopicID]);

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('Done.');
    }
}
